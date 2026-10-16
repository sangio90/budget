<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\FiscalClient;
use App\Models\FiscalDeadline;
use App\Models\FiscalYear;
use App\Models\Transaction;
use App\Services\Fiscale\GeneratoreScadenze;
use App\Services\Fiscale\ProiezioneFiscale;
use App\Services\Fiscale\Scadenziario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FiscaleController extends Controller
{
    /** Campi del form parametri espressi in percentuale (salvati come frazione). */
    private const PERCENTUALI = ['aliquota_inps', 'aliquota_iva', 'aliquota_ritenuta', 'aliquota_add_comunale', 'accantonamento_perc'];

    public function index(Request $request, ProiezioneFiscale $proiezione, Scadenziario $scadenziario)
    {
        $userId = auth()->id();
        $oggi   = $proiezione->oggi();
        $anno   = (int) $request->input('anno', $oggi->year);

        $parametri = $proiezione->parametri($userId, $anno);
        $ipotesi   = $proiezione->ipotesi($userId, $anno);
        $risultato = $proiezione->risultato($userId, $anno);
        $triennio  = $proiezione->triennio($userId, $anno);

        $righe     = $scadenziario->righe($userId);
        $righeAnno = $righe->filter(fn($r) => $r->scadenza->year === $anno)->values();
        $totali    = $scadenziario->totaliPerData($righeAnno);
        $fondo     = $scadenziario->fondoTasse($userId, $oggi, $righe);

        // IVA versata per l'anno di competenza (righe chiuse)
        $ivaVersata = $righe->filter(fn($r) => $r->tributo === 'IVA' && (int) $r->anno_competenza === $anno && $r->chiusa())
            ->sum(fn($r) => $r->importo_pagato !== null ? (float) $r->importo_pagato : $r->da_versare_calc);

        // Incassato lordo annuo (annualizzato per l'anno in corso) per l'accantonamento in %
        $consuntivo     = $ipotesi['consuntivo'];
        $mesi           = $ipotesi['mesi'];
        $incassatoAnnuo = $consuntivo ? ($mesi > 0 && $mesi < 12 ? $consuntivo['incassato'] * 12 / $mesi : $consuntivo['incassato']) : null;
        $accantonamento = [
            'mensile'      => $risultato['accantonamento_mensile'],
            'perc'         => $incassatoAnnuo > 0 ? $risultato['accantonamento_annuo'] / $incassatoAnnuo : null,
            'obiettivo'    => (float) $parametri->accantonamento_perc,
            'obiettivo_mensile' => $incassatoAnnuo ? $incassatoAnnuo / 12 * (float) $parametri->accantonamento_perc : null,
        ];

        // Clienti: regole salvate + causali di incasso senza regola (fattore di default)
        $clienti = FiscalClient::where('user_id', $userId)->orderBy('causale')->get();
        $causaliSenzaRegola = Transaction::where('user_id', $userId)->where('tipo', 'entrata')
            ->distinct()->orderBy('causale')->pluck('causale')
            ->reject(fn($c) => $clienti->contains(fn($cl) => mb_strtolower($cl->causale) === mb_strtolower($c)))
            ->values();

        $macroCategorie = BudgetCategory::orderBy('categoria')->get(['categoria', 'aziendale'])
            ->groupBy('categoria')->map(fn($g) => $g->contains('aziendale', true));

        $primo = (int) (FiscalYear::where('user_id', $userId)->min('anno') ?? $oggi->year);
        $anni  = range(max($oggi->year + 2, $anno), min($primo, $oggi->year, $anno));

        return view('fiscale.index', compact(
            'anno', 'anni', 'oggi', 'parametri', 'ipotesi', 'risultato', 'triennio', 'righeAnno', 'totali', 'fondo',
            'ivaVersata', 'consuntivo', 'accantonamento', 'clienti', 'causaliSenzaRegola', 'macroCategorie'
        ));
    }

    public function updateParametri(Request $request, int $anno)
    {
        abort_unless($anno >= 2000 && $anno <= 2100, 404);

        $regole = ['note' => 'nullable|string|max:2000',
                   'scaglioni_irpef' => 'nullable|string|max:255', 'scaglioni_add_regionale' => 'nullable|string|max:255'];
        foreach (FiscalYear::CAMPI_NUMERICI as $campo) {
            $regole[$campo] = 'nullable|numeric|between:-99999999,99999999';
        }
        $dati = $request->validate($regole);

        foreach (self::PERCENTUALI as $campo) {
            if (isset($dati[$campo]) && $dati[$campo] !== null) {
                $dati[$campo] = round((float) $dati[$campo] / 100, 6);
            }
        }
        foreach (['scaglioni_irpef', 'scaglioni_add_regionale'] as $campo) {
            $dati[$campo] = $this->leggiScaglioni($dati[$campo] ?? null, $campo);
        }
        // I campi obbligatori in tabella non si possono svuotare: si tiene il valore attuale o il default
        $parametri = FiscalYear::perAnno(auth()->id(), $anno);
        foreach (['aliquota_inps', 'massimale_inps', 'aliquota_iva', 'aliquota_ritenuta', 'aliquota_add_comunale', 'redditi_fabbricati',
                  'cedolare_secca', 'fondo_pensione', 'detrazioni', 'credito_irpef_iniziale', 'credito_iva_iniziale', 'accantonamento_perc'] as $campo) {
            if (! isset($dati[$campo])) {
                $dati[$campo] = $parametri->getAttribute($campo) ?? 0;
            }
        }

        $parametri->fill($dati)->save();

        return redirect()->route('fiscale.index', ['anno' => $anno])->with('success', "Parametri fiscali $anno salvati.");
    }

    public function storeCliente(Request $request)
    {
        $dati = $request->validate([
            'causale'           => 'required|string|max:255',
            'aliquota_iva'      => 'required|numeric|between:0,100',
            'aliquota_ritenuta' => 'required|numeric|between:0,100',
            'note'              => 'nullable|string|max:255',
        ]);

        FiscalClient::updateOrCreate(
            ['user_id' => auth()->id(), 'causale' => trim($dati['causale'])],
            ['aliquota_iva' => $dati['aliquota_iva'] / 100, 'aliquota_ritenuta' => $dati['aliquota_ritenuta'] / 100, 'note' => $dati['note'] ?? null]
        );

        return back()->with('success', 'Regola cliente salvata.');
    }

    public function destroyCliente(FiscalClient $client)
    {
        abort_unless($client->user_id === auth()->id(), 403);
        $client->delete();

        return back()->with('success', 'Regola cliente eliminata: si usa il fattore di default.');
    }

    public function updateCategorie(Request $request)
    {
        $scelte = array_values(array_filter((array) $request->input('categorie', []), 'is_string'));

        BudgetCategory::query()->update(['aziendale' => false]);
        if ($scelte) {
            BudgetCategory::whereIn('categoria', $scelte)->update(['aziendale' => true]);
        }

        return back()->with('success', 'Categorie deducibili aggiornate.');
    }

    public function storeScadenza(Request $request, Scadenziario $scadenziario)
    {
        $dati = $this->validaScadenza($request);

        FiscalDeadline::create($dati + ['user_id' => auth()->id(), 'manuale' => true]);
        $scadenziario->aggiornaDaVersare(auth()->id());

        return redirect()->route('fiscale.index', ['anno' => substr($dati['scadenza'], 0, 4)])->with('success', 'Scadenza inserita.');
    }

    public function editScadenza(FiscalDeadline $deadline)
    {
        abort_unless($deadline->user_id === auth()->id(), 403);

        $f24 = Transaction::where('user_id', auth()->id())->where('tipo', 'f24')
            ->whereYear('data', '>=', $deadline->scadenza->year - 1)
            ->orderByDesc('data')->get(['id', 'data', 'importo', 'causale']);

        return view('fiscale.edit-scadenza', ['scadenza' => $deadline, 'f24' => $f24]);
    }

    public function updateScadenza(Request $request, FiscalDeadline $deadline, Scadenziario $scadenziario)
    {
        abort_unless($deadline->user_id === auth()->id(), 403);

        $dati = $this->validaScadenza($request);
        // Modificata a mano: "Genera stime" non la tocca più, a meno che si scelga di lasciarla automatica
        $dati['manuale'] = ! ($request->boolean('automatica') && $deadline->chiave && $dati['stato'] === 'stimato');

        $deadline->update($dati);
        $scadenziario->aggiornaDaVersare(auth()->id());

        return redirect()->route('fiscale.index', ['anno' => $deadline->scadenza->year])->with('success', 'Scadenza aggiornata.');
    }

    public function destroyScadenza(FiscalDeadline $deadline, Scadenziario $scadenziario)
    {
        abort_unless($deadline->user_id === auth()->id(), 403);
        $deadline->delete();
        $scadenziario->aggiornaDaVersare(auth()->id());

        return back()->with('success', 'Scadenza eliminata.');
    }

    public function generaStime(Request $request, GeneratoreScadenze $generatore)
    {
        $anno  = (int) $request->validate(['anno' => 'required|integer|between:2000,2100'])['anno'];
        $esito = $generatore->genera(auth()->id(), $anno);

        return redirect()->route('fiscale.index', ['anno' => $anno])->with('success', sprintf(
            'Stime %d: %d create, %d aggiornate, %d eliminate, %d non toccate (pagate, da pagare o modificate a mano).',
            $anno, $esito['creati'], $esito['aggiornati'], $esito['eliminati'], $esito['protetti']
        ));
    }

    private function validaScadenza(Request $request): array
    {
        $dati = $request->validate([
            'scadenza'         => 'required|date',
            'tributo'          => ['required', Rule::in(FiscalDeadline::TRIBUTI)],
            'descrizione'      => 'required|string|max:255',
            'anno_competenza'  => 'required|integer|between:2000,2100',
            'importo_previsto' => 'required|numeric|between:-99999999,99999999',
            'compensato'       => 'nullable|numeric|between:0,99999999',
            'importo_pagato'   => 'nullable|numeric|between:0,99999999',
            'data_pagamento'   => 'nullable|date',
            'stato'            => ['required', Rule::in(array_keys(FiscalDeadline::STATI))],
            'transaction_id'   => ['nullable', 'integer', Rule::exists('transactions', 'id')->where('user_id', auth()->id())],
            'note'             => 'nullable|string|max:1000',
        ]);
        return $dati;
    }

    /** "28000:23; 50000:33; *:43" → [['fino' => 28000, 'aliquota' => 0.23], …]. Vuoto = default. */
    private function leggiScaglioni(?string $testo, string $campo): ?array
    {
        if ($testo === null || trim($testo) === '') {
            return null;
        }

        $scaglioni = [];
        foreach (array_filter(array_map('trim', explode(';', $testo))) as $pezzo) {
            if (! preg_match('/^(\*|oltre|[\d.]+)\s*:\s*([\d.,]+)$/i', $pezzo, $m)) {
                throw ValidationException::withMessages([$campo => "Formato non valido: \"$pezzo\". Usa es. 28000:23; 50000:33; *:43"]);
            }
            $scaglioni[] = [
                'fino'     => is_numeric($m[1]) ? (float) $m[1] : null,
                'aliquota' => round((float) str_replace(',', '.', $m[2]) / 100, 6),
            ];
        }
        if (end($scaglioni)['fino'] !== null) {
            $scaglioni[] = ['fino' => null, 'aliquota' => end($scaglioni)['aliquota']];
        }

        return $scaglioni;
    }
}
