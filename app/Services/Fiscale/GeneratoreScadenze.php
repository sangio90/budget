<?php

namespace App\Services\Fiscale;

use App\Models\FiscalDeadline;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * "Genera/aggiorna stime": crea o ricalcola le scadenze stimate che si pagano in un anno.
 * Non tocca le righe pagate, compensate, da pagare o modificate a mano (manuale).
 *
 * Scadenze generate per l'anno Y:
 *  - IVA: saldo 4° trim. Y-1 (16/03), 1°-3° trimestre Y (16/05, 20/08, 16/11, +1% interessi),
 *         acconto Y (27/12, metodo storico 88%)
 *  - INPS: saldo Y-1 + 1° acconto Y (30/06), 2° acconto Y (30/11); acconti = 40% + 40% del dovuto Y-1
 *  - IRPEF: saldo Y-1 (negativo = credito), acconti Y 50% + 50% se dovuti
 *  - Addizionale regionale: saldo Y-1; comunale: saldo Y-1 + acconto Y (30%)
 *  - Cedolare secca: saldo Y-1, acconti Y 50% + 50%
 * Le scadenze che cadono di sabato o domenica slittano al lunedì.
 */
class GeneratoreScadenze
{
    public const ORDINE = [
        'iva' => 0, 'inps:saldo' => 10, 'inps:acconto1' => 11, 'inps:acconto2' => 12,
        'irpef:saldo' => 20, 'irpef:acconto1' => 21, 'irpef:acconto2' => 22,
        'addreg:saldo' => 30, 'addcom:saldo' => 40, 'addcom:acconto' => 41,
        'ced:saldo' => 50, 'ced:acconto1' => 51, 'ced:acconto2' => 52,
    ];

    /** Sotto questa soglia gli acconti IRPEF non sono dovuti. */
    private const MINIMO_ACCONTO_IRPEF = 51.65;

    public function __construct(
        private ProiezioneFiscale $proiezione,
        private Scadenziario $scadenziario,
    ) {}

    /** Scadenza al primo giorno lavorativo (sabato/domenica → lunedì). */
    public static function giornoLavorativo(CarbonInterface $d): Carbon
    {
        $d = Carbon::instance($d)->startOfDay();
        if ($d->isSaturday()) return $d->addDays(2);
        if ($d->isSunday())   return $d->addDay();

        return $d;
    }

    /** Le scadenze stimate dell'anno, senza salvarle: [chiave => attributi]. */
    public function calcola(int $userId, int $anno): array
    {
        $prec   = $anno - 1;
        $p      = $this->proiezione->parametri($userId, $anno);
        $rPrec  = $this->proiezione->risultato($userId, $prec);
        $data   = fn(int $m, int $g, ?int $a = null) => self::giornoLavorativo(Carbon::create($a ?? $anno, $m, $g));
        $righe  = [];
        $riga   = function (string $chiave, string $tributo, string $descr, int $competenza, CarbonInterface $scad, float $importo, string $nota) use (&$righe) {
            [$t, $tipo] = array_pad(explode(':', $chiave), 2, null);
            $righe[$chiave] = [
                'tributo'          => $tributo,
                'descrizione'      => $descr,
                'anno_competenza'  => $competenza,
                'scadenza'         => $scad->toDateString(),
                'importo_previsto' => round($importo, 2),
                'ordine'           => self::ORDINE[$t === 'iva' ? 'iva' : "$t:$tipo"] ?? 90,
                'note'             => $nota,
            ];
        };

        // --- IVA ---------------------------------------------------------------
        $ivaQ4Prec     = $this->ivaTrimestre($userId, $prec, 4);
        $accontoPrec   = $this->importoRiga($userId, "iva:acconto:$prec") ?? round(0.88 * ($this->proiezione->parametri($userId, $prec)->num('iva_base_acconto') ?? $this->ivaTrimestre($userId, $prec - 1, 4)), 2);
        $riga("iva:saldo:$prec", 'IVA', '4° trimestre / saldo annuale ' . $prec, $prec, $data(3, 16),
            max(0, $ivaQ4Prec - $accontoPrec), 'Stima IVA 4° trimestre ' . number_format($ivaQ4Prec, 2, ',', '.') . ' − acconto ' . number_format($accontoPrec, 2, ',', '.'));

        foreach ([1 => [5, 16], 2 => [8, 20], 3 => [11, 16]] as $q => [$m, $g]) {
            $iva = $this->ivaTrimestre($userId, $anno, $q);
            $riga("iva:q$q:$anno", 'IVA', "IVA {$q}° trimestre $anno", $anno, $data($m, $g), $iva * 1.01,
                ($this->trimestreConcluso($anno, $q) ? 'IVA sugli incassi del trimestre' : 'Stima: IVA annua / 4') . ' + 1% interessi');
        }

        $base = $p->num('iva_base_acconto') ?? $ivaQ4Prec;
        $riga("iva:acconto:$anno", 'IVA', "Acconto IVA $anno", $anno, $data(12, 27),
            0.88 * $base, '88% × ' . number_format($base, 2, ',', '.') . ' (metodo storico)');

        // --- INPS --------------------------------------------------------------
        $inpsPrec    = $p->num('prec_inps_dovuto') ?? $this->proiezione->inpsDichiarato($userId, $prec);
        $accontiInps = $this->sommaRighe($userId, ["inps:acconto1:$prec", "inps:acconto2:$prec"])
            ?? $this->proiezione->accontiInpsVersati($userId, $prec);
        $riga("inps:saldo:$prec", 'INPS', "INPS gestione separata – saldo $prec", $prec, $data(6, 30),
            max(0, $inpsPrec - $accontiInps), 'Dovuto ' . number_format($inpsPrec, 2, ',', '.') . ' − acconti ' . number_format($accontiInps, 2, ',', '.'));
        foreach ([1 => [6, 30], 2 => [11, 30]] as $n => [$m, $g]) {
            $riga("inps:acconto$n:$anno", 'INPS', "INPS – {$n}° acconto $anno", $anno, $data($m, $g),
                0.4 * $inpsPrec, '40% × ' . number_format($inpsPrec, 2, ',', '.'));
        }

        // --- IRPEF -------------------------------------------------------------
        $irpefNetta   = $p->num('prec_irpef_netta') ?? $rPrec['irpef_netta'];
        $ritenute     = $p->num('prec_ritenute') ?? $rPrec['ritenute'];
        $accontiIrpef = $this->sommaRighe($userId, ["irpef:acconto1:$prec", "irpef:acconto2:$prec"]) ?? 0;
        $saldoIrpef   = $irpefNetta - $ritenute - $accontiIrpef;
        $riga("irpef:saldo:$prec", 'IRPEF', "IRPEF – saldo $prec" . ($saldoIrpef < 0 ? ' (a credito)' : ''), $prec, $data(6, 30),
            $saldoIrpef, 'IRPEF netta ' . number_format($irpefNetta, 0, ',', '.') . ' − ritenute ' . number_format($ritenute, 0, ',', '.') . ' − acconti ' . number_format($accontiIrpef, 0, ',', '.'));
        $baseAcconto = $irpefNetta - $ritenute;
        foreach ([1 => [6, 30], 2 => [11, 30]] as $n => [$m, $g]) {
            $riga("irpef:acconto$n:$anno", 'IRPEF', "IRPEF – {$n}° acconto $anno", $anno, $data($m, $g),
                $baseAcconto > self::MINIMO_ACCONTO_IRPEF ? 0.5 * $baseAcconto : 0, '50% di (IRPEF netta − ritenute) ' . $prec);
        }

        // --- Addizionali -------------------------------------------------------
        $riga("addreg:saldo:$prec", 'Addizionale regionale', "Addizionale regionale – saldo $prec", $prec, $data(6, 30),
            $rPrec['add_regionale'], 'Scaglioni regionali sull\'imponibile ' . $prec);
        $accontoCom = $this->sommaRighe($userId, ["addcom:acconto:$prec"]) ?? 0;
        $riga("addcom:saldo:$prec", 'Addizionale comunale', "Addizionale comunale – saldo $prec", $prec, $data(6, 30),
            max(0, $rPrec['add_comunale'] - $accontoCom), 'Addizionale ' . number_format($rPrec['add_comunale'], 0, ',', '.') . ' − acconto ' . number_format($accontoCom, 0, ',', '.'));
        $riga("addcom:acconto:$anno", 'Addizionale comunale', "Addizionale comunale – acconto $anno", $anno, $data(6, 30),
            round(0.3 * $rPrec['add_comunale'], 0), '30% × addizionale ' . $prec);

        // --- Cedolare secca ----------------------------------------------------
        $cedPrec    = $p->num('prec_cedolare') ?? $rPrec['cedolare'];
        $accontiCed = $this->sommaRighe($userId, ["ced:acconto1:$prec", "ced:acconto2:$prec"]) ?? $cedPrec;
        $riga("ced:saldo:$prec", 'Cedolare secca', "Cedolare secca – saldo $prec", $prec, $data(6, 30),
            max(0, $cedPrec - $accontiCed), 'Cedolare ' . number_format($cedPrec, 0, ',', '.') . ' − acconti ' . number_format($accontiCed, 0, ',', '.'));
        foreach ([1 => [6, 30], 2 => [11, 30]] as $n => [$m, $g]) {
            $riga("ced:acconto$n:$anno", 'Cedolare secca', "Cedolare secca – {$n}° acconto $anno", $anno, $data($m, $g),
                round($cedPrec / 2, 0), '50% × cedolare ' . $prec);
        }

        return $righe;
    }

    /**
     * Salva le stime: crea le righe nuove, aggiorna quelle ancora "stimato" e non manuali,
     * elimina le stimate che risultano a zero. Restituisce i conteggi.
     */
    public function genera(int $userId, int $anno): array
    {
        $esito = ['creati' => 0, 'aggiornati' => 0, 'protetti' => 0, 'eliminati' => 0];

        foreach ($this->calcola($userId, $anno) as $chiave => $attr) {
            $riga = FiscalDeadline::where('user_id', $userId)->where('chiave', $chiave)->first();

            if ($riga && ($riga->manuale || $riga->stato !== 'stimato')) {
                $esito['protetti']++;
                continue;
            }
            if (abs($attr['importo_previsto']) < 0.005) {
                if ($riga) {
                    $riga->delete();
                    $esito['eliminati']++;
                }
                continue;
            }

            if ($riga) {
                $riga->fill($attr + ['compensato' => null])->save();
                $esito['aggiornati']++;
            } else {
                FiscalDeadline::create($attr + ['user_id' => $userId, 'chiave' => $chiave, 'stato' => 'stimato', 'manuale' => false]);
                $esito['creati']++;
            }
        }

        $this->scadenziario->aggiornaDaVersare($userId);

        return $esito;
    }

    /** IVA dovuta di un trimestre: dagli incassi se il trimestre è concluso, altrimenti IVA annua stimata / 4. */
    public function ivaTrimestre(int $userId, int $anno, int $q): float
    {
        if ($this->trimestreConcluso($anno, $q)) {
            $c = $this->proiezione->ipotesi($userId, $anno)['consuntivo']
                ?? app(DatiFiscali::class)->consuntivo($userId, $anno);

            return round($c['iva_trimestri'][$q] ?? 0, 2);
        }

        $p = $this->proiezione->parametri($userId, $anno);
        $v = $this->proiezione->ipotesi($userId, $anno)['valori'];

        return round(($p->num('iva_annua_prevista') ?? $v['iva_annua'] ?? $v['iva_incassata'] ?? 0) / 4, 2);
    }

    private function trimestreConcluso(int $anno, int $q): bool
    {
        return Carbon::create($anno, $q * 3, 1)->endOfMonth()->lt($this->proiezione->oggi());
    }

    private function importoRiga(int $userId, string $chiave): ?float
    {
        $v = FiscalDeadline::where('user_id', $userId)->where('chiave', $chiave)->value('importo_previsto');

        return $v === null ? null : (float) $v;
    }

    /** Somma degli importi delle righe con queste chiavi (null se nessuna esiste). */
    private function sommaRighe(int $userId, array $chiavi): ?float
    {
        $righe = FiscalDeadline::where('user_id', $userId)->whereIn('chiave', $chiavi)->get();

        return $righe->isEmpty() ? null : (float) $righe->sum('importo_previsto');
    }
}
