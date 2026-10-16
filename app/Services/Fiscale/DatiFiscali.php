<?php

namespace App\Services\Fiscale;

use App\Models\BudgetExpense;
use App\Models\ExtraIncome;
use App\Models\FiscalClient;
use App\Models\FiscalYear;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Consuntivo fiscale di un anno (fino a una data) dai dati dell'app:
 *  - transactions entrata → incassi clienti, imponibile, IVA e ritenute (regole in fiscal_clients)
 *  - transactions uscita  → collaboratori (costo deducibile)
 *  - transactions f24     → F24 versati
 *  - budget_expenses delle categorie "aziendale" → costi dell'attività; le altre → spese famiglia
 *  - extra_incomes legate a un immobile → affitti (e rimborsi spese immobili)
 * Aggregazione in PHP: niente YEAR()/MONTH(), che non esistono in SQLite.
 */
class DatiFiscali
{
    /** @var array<int, Collection> regole clienti per utente, indicizzate per causale minuscola */
    private array $clienti = [];

    public function consuntivo(int $userId, int $anno, ?CarbonInterface $fino = null): array
    {
        $p      = FiscalYear::perAnno($userId, $anno);
        $limite = fn($q) => $fino ? $q->whereDate('data', '<=', $fino->toDateString()) : $q;

        $transazioni = $limite(Transaction::where('user_id', $userId)->whereYear('data', $anno))
            ->get(['tipo', 'importo', 'data', 'causale']);

        $incassato = $imponibile = $iva = $ritenute = $compensiRa = 0.0;
        $ivaTrimestri = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        $perCliente   = [];

        foreach ($transazioni->where('tipo', 'entrata') as $t) {
            [$aliqIva, $aliqRit] = $this->regola($userId, $t->causale, $p);
            $incasso = (float) $t->importo;
            $imp     = $incasso / (1 + $aliqIva - $aliqRit);

            $incassato  += $incasso;
            $imponibile += $imp;
            $iva        += $imp * $aliqIva;
            $ritenute   += $imp * $aliqRit;
            if ($aliqRit > 0) {
                $compensiRa += $imp;
            }
            $ivaTrimestri[intdiv($t->data->month - 1, 3) + 1] += $imp * $aliqIva;

            $c = $perCliente[$t->causale] ??= ['incassato' => 0.0, 'imponibile' => 0.0, 'fattore' => 1 + $aliqIva - $aliqRit];
            $perCliente[$t->causale]['incassato']  = $c['incassato'] + $incasso;
            $perCliente[$t->causale]['imponibile'] = $c['imponibile'] + $imp;
        }
        uasort($perCliente, fn($a, $b) => $b['incassato'] <=> $a['incassato']);

        $collaboratori = (float) $transazioni->where('tipo', 'uscita')->sum('importo');
        $f24           = (float) $transazioni->where('tipo', 'f24')->sum('importo');

        $spese = $limite(BudgetExpense::with('category:id,categoria,aziendale')
            ->where('user_id', $userId)->whereYear('data', $anno))
            ->get(['budget_category_id', 'importo']);
        $costiAziendali = (float) $spese->filter(fn($e) => (bool) $e->category?->aziendale)->sum('importo');
        $speseFamiglia  = (float) $spese->reject(fn($e) => (bool) $e->category?->aziendale)->sum('importo');

        $affitti = (float) $limite(ExtraIncome::where('user_id', $userId)->whereYear('data', $anno))
            ->whereNotNull('immobile')->where('immobile', '!=', '')
            ->sum('importo');

        return [
            'incassato'       => round($incassato, 2),
            'imponibile'      => round($imponibile, 2),
            'iva_incassata'   => round($iva, 2),
            'ritenute'        => round($ritenute, 2),
            'compensi_ra'     => round($compensiRa, 2),
            'collaboratori'   => round($collaboratori, 2),
            'costi_aziendali' => round($costiAziendali, 2),
            'costi'           => round($collaboratori + $costiAziendali, 2),
            'f24'             => round($f24, 2),
            'affitti'         => round($affitti, 2),
            'spese_famiglia'  => round($speseFamiglia, 2),
            'iva_trimestri'   => array_map(fn($v) => round($v, 2), $ivaTrimestri),
            'per_cliente'     => $perCliente,
        ];
    }

    /** [aliquota IVA, aliquota ritenuta] del cliente; default dai parametri dell'anno (22% / 20%). */
    public function regola(int $userId, ?string $causale, FiscalYear $p): array
    {
        $this->clienti[$userId] ??= FiscalClient::where('user_id', $userId)->get()
            ->keyBy(fn($c) => mb_strtolower(trim($c->causale)));

        $c = $this->clienti[$userId]->get(mb_strtolower(trim((string) $causale)));

        return $c
            ? [(float) $c->aliquota_iva, (float) $c->aliquota_ritenuta]
            : [(float) $p->aliquota_iva, (float) $p->aliquota_ritenuta];
    }
}
