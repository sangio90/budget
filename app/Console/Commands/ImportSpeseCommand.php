<?php

namespace App\Console\Commands;

use App\Models\BudgetCategory;
use App\Models\BudgetExpense;
use App\Services\EstrattoConto\Classificatore;
use App\Services\EstrattoConto\Classificazione;
use App\Services\EstrattoConto\Movimento;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pipeline comune agli import di spese da fonti esterne (estratto conto, Amazon, PayPal):
 * lettura file → regole di classificazione → controllo doppioni → report → inserimento.
 */
abstract class ImportSpeseCommand extends Command
{
    protected const OPZIONI = '
                            {--dry-run : Mostra cosa verrebbe inserito senza scrivere nulla}
                            {--user=1 : ID dell\'utente a cui assegnare le spese}
                            {--giorni=3 : Tolleranza in giorni per riconoscere i doppioni (stesso importo)}
                            {--da= : Considera solo i movimenti da questa data (YYYY-MM-DD)}
                            {--a= : Considera solo i movimenti fino a questa data (YYYY-MM-DD)}
                            {--tsv= : Scrive il report completo in un file tab-separated (anteprima)}
                            {--force : Inserisci senza chiedere conferma}';

    private const NUOVO = 'NUOVO';
    private const DOPPIONE = 'DOPPIONE';
    private const IGNORATO = 'IGNORATO';
    private const DA_CLASSIFICARE = 'DA CLASSIFICARE';

    /** @return Movimento[] */
    abstract protected function leggi(string $file): array;

    /** Chiave della lista di regole in config/estratto_conto.php. */
    abstract protected function regole(): string;

    public function handle(): int
    {
        $userId = (int) $this->option('user');
        $giorni = (int) $this->option('giorni');
        $da = $this->option('da') ? CarbonImmutable::parse($this->option('da'))->startOfDay() : null;
        $a  = $this->option('a')  ? CarbonImmutable::parse($this->option('a'))->startOfDay()  : null;

        // 1. Lettura dei file
        $movimenti = [];
        foreach ($this->argument('file') as $file) {
            try {
                $letti = $this->leggi($file);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }
            $this->line(sprintf('%s: %d movimenti', basename($file), count($letti)));
            $movimenti = array_merge($movimenti, $letti);
        }
        $movimenti = array_values(array_filter($movimenti, fn(Movimento $m) =>
            (!$da || $m->dataOperazione >= $da) && (!$a || $m->dataOperazione <= $a)));
        if ($movimenti === []) {
            $this->warn('Nessun movimento trovato' . ($da || $a ? ' nel periodo indicato.' : '.'));
            return self::SUCCESS;
        }
        usort($movimenti, fn(Movimento $x, Movimento $y) => [$x->dataOperazione, $x->importo] <=> [$y->dataOperazione, $y->importo]);

        // 2. Categorie: le regole devono puntare a nomi esistenti e univoci
        $classificatore = Classificatore::fromConfig($this->regole());
        $categorie = BudgetCategory::all()->groupBy('nome');
        foreach ($classificatore->categorieUsate() as $nome) {
            $n = $categorie->get($nome)?->count() ?? 0;
            if ($n !== 1) {
                $this->error($n === 0
                    ? "La regola usa la categoria \"{$nome}\" che non esiste nel DB."
                    : "La categoria \"{$nome}\" è ambigua ({$n} righe con lo stesso nome).");
                return self::FAILURE;
            }
        }

        // 3. Spese già presenti nel periodo, per il controllo doppioni
        $date = array_map(fn(Movimento $m) => $m->dataOperazione, $movimenti);
        $esistenti = BudgetExpense::where('user_id', $userId)
            ->whereBetween('data', [min($date)->subDays($giorni), max($date)->addDays($giorni)])
            ->orderBy('data')
            ->get();
        $usate = [];

        // 4. Classificazione
        $righe = [];
        $report = [];
        $daInserire = [];
        foreach ($movimenti as $mov) {
            $cls = $classificatore->classifica($mov);
            $esito = $this->esito($mov, $cls, $esistenti, $usate, $giorni);
            $categoria = $cls->categoria ? $categorie->get($cls->categoria)->first() : null;

            if ($esito === self::NUOVO) {
                $daInserire[] = [
                    'user_id'            => $userId,
                    'budget_category_id' => $categoria->id,
                    'importo'            => $mov->importoAssoluto(),
                    'data'               => $mov->dataOperazione->toDateString(),
                    'note'               => $cls->note,
                ];
            }
            $righe[] = [
                $mov->dataOperazione->format('d/m/Y') . ($mov->nonContabilizzato ? ' *' : ''),
                number_format($mov->importoAssoluto(), 2, ',', '.'),
                mb_strimwidth($mov->esercente ?? $mov->descrizione, 0, 42, '…'),
                $cls->categoria ?? '',
                $cls->note ?? '',
                $esito,
            ];
            $report[] = [
                'esito'       => $esito,
                'data'        => $mov->dataOperazione->toDateString(),
                'importo'     => number_format($mov->importoAssoluto(), 2, ',', ''),
                'gruppo'      => $categoria?->categoria ?? '',
                'categoria'   => $categoria?->nome ?? '',
                'nota'        => $cls->note ?? '',
                'esercente'   => $mov->esercente ?? '',
                'conto'       => $mov->iban ? '…' . substr($mov->iban, -4) : '',
                'descrizione' => $mov->descrizione,
            ];
        }

        if ($this->option('tsv')) {
            $this->scriviTsv($this->option('tsv'), $report);
        }

        // 5. Report
        $this->newLine();
        $this->table(['Data', 'Importo', 'Esercente / descrizione', 'Categoria', 'Nota', 'Esito'], $righe);
        $conteggi = array_count_values(array_map(fn($r) => explode(' ', $r[5], 2)[0], $righe));
        $this->line(sprintf(
            'Nuove: %d   Doppioni: %d   Ignorate: %d   Da classificare: %d   (* = non ancora contabilizzato)',
            $conteggi['NUOVO'] ?? 0, $conteggi['DOPPIONE'] ?? 0, $conteggi['IGNORATO'] ?? 0, $conteggi['DA'] ?? 0,
        ));

        $daClassificare = array_filter($movimenti, fn(Movimento $m) => $m->isUscita() && $classificatore->classifica($m)->isDaClassificare());
        if ($daClassificare !== []) {
            $this->newLine();
            $this->warn("Movimenti senza regola (aggiungila in config/estratto_conto.php, lista \"{$this->regole()}\"):");
            foreach ($daClassificare as $mov) {
                $this->line(sprintf('  %s  %9s  %s', $mov->dataOperazione->format('d/m/Y'), number_format($mov->importoAssoluto(), 2, ',', '.'), $mov->descrizione));
            }
        }

        // 6. Inserimento
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run: nessuna scrittura effettuata.');
            return self::SUCCESS;
        }
        if ($daInserire === []) {
            $this->info('Niente da inserire.');
            return self::SUCCESS;
        }
        $totale = number_format(array_sum(array_column($daInserire, 'importo')), 2, ',', '.');
        if (!$this->option('force') && !$this->confirm(sprintf('Inserire %d spese per un totale di %s EUR?', count($daInserire), $totale), false)) {
            $this->line('Annullato.');
            return self::SUCCESS;
        }
        DB::transaction(function () use ($daInserire) {
            foreach ($daInserire as $riga) {
                BudgetExpense::create($riga);
            }
        });
        $this->info(sprintf('Inserite %d spese (%s EUR).', count($daInserire), $totale));

        return self::SUCCESS;
    }

    /** Scrive il report come file tab-separated (le righe NUOVO prima, poi le altre). */
    private function scriviTsv(string $path, array $report): void
    {
        usort($report, fn($a, $b) => [$a['esito'] !== self::NUOVO, $a['data']] <=> [$b['esito'] !== self::NUOVO, $b['data']]);

        $pulisci = fn($v) => str_replace(["\t", "\n", "\r"], ' ', (string) $v);
        $righe = [implode("\t", array_keys($report[0]))];
        foreach ($report as $r) {
            $righe[] = implode("\t", array_map($pulisci, $r));
        }
        file_put_contents($path, implode("\n", $righe) . "\n");
        $this->line("Report scritto in {$path}");
    }

    /**
     * Decide cosa fare del movimento. Un doppione è una spesa già presente con lo
     * stesso importo e data entro la tolleranza; ogni spesa esistente può
     * "coprire" un solo movimento, così due acquisti uguali nello stesso giorno
     * vengono contati correttamente.
     */
    private function esito(Movimento $mov, Classificazione $cls, $esistenti, array &$usate, int $giorni): string
    {
        if (!$mov->isUscita()) {
            return self::IGNORATO . ' (entrata)';
        }
        if ($cls->isIgnorata()) {
            return self::IGNORATO . ' (' . $cls->motivoIgnorata . ')';
        }
        if ($cls->isDaClassificare()) {
            return self::DA_CLASSIFICARE;
        }
        foreach ($esistenti as $spesa) {
            if (isset($usate[$spesa->id])) {
                continue;
            }
            if (abs((float) $spesa->importo - $mov->importoAssoluto()) < 0.005
                && abs($spesa->data->diffInDays($mov->dataOperazione, false)) <= $giorni) {
                $usate[$spesa->id] = true;
                return self::DOPPIONE . sprintf(' (#%d del %s)', $spesa->id, $spesa->data->format('d/m'));
            }
        }
        return self::NUOVO;
    }
}
