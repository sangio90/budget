<?php

namespace App\Services\Fiscale;

use App\Models\FiscalYear;

/**
 * Calcolo di imposte e contributi di un anno di competenza, come il foglio
 * "Proiezione 2026-2028" dell'Excel fiscale. Nessun accesso al database.
 *
 * Ipotesi in ingresso (importi annui):
 *  - compensi        imponibile fatturato (incassi / fattore cliente)
 *  - compensi_ra     parte dei compensi soggetta a ritenuta d'acconto
 *  - costi           costi deducibili dell'attività (collaboratori, ufficio, software, commercialista)
 *  - affitti         affitti incassati (cassa), solo per il netto
 *  - inps_deducibile INPS versato nell'anno (deducibile per cassa)
 *  - iva_annua       IVA da versare nell'anno (solo per l'accantonamento)
 *  - imu             IMU dell'anno (solo per l'accantonamento)
 *  - spese_famiglia  spese personali annue (solo per il risparmio)
 */
class CalcoloImposte
{
    /** Imposta a scaglioni: [['fino' => 28000, 'aliquota' => 0.23], …, ['fino' => null, 'aliquota' => 0.43]]. */
    public static function aScaglioni(float $base, array $scaglioni): float
    {
        $imposta = 0.0;
        $da      = 0.0;
        foreach ($scaglioni as $s) {
            if ($base <= $da) {
                break;
            }
            $fino     = $s['fino'] === null || $s['fino'] === '' ? INF : (float) $s['fino'];
            $imposta += (min($base, $fino) - $da) * (float) $s['aliquota'];
            $da       = $fino;
        }

        return $imposta;
    }

    /**
     * Dettaglio per scaglione: per ogni fascia [da, fino, aliquota, base tassata nella fascia, imposta].
     * La somma delle imposte coincide con aScaglioni().
     */
    public static function dettaglioScaglioni(float $base, array $scaglioni): array
    {
        $righe = [];
        $da    = 0.0;
        foreach ($scaglioni as $s) {
            $fino     = $s['fino'] === null || $s['fino'] === '' ? null : (float) $s['fino'];
            $limite   = $fino ?? INF;
            $tassata  = max(0.0, min($base, $limite) - $da);
            $righe[]  = [
                'da'       => $da,
                'fino'     => $fino,
                'aliquota' => (float) $s['aliquota'],
                'base'     => $tassata,
                'imposta'  => $tassata * (float) $s['aliquota'],
            ];
            $da = $limite;
            if ($da === INF) {
                break;
            }
        }

        return $righe;
    }

    public function calcola(array $i, FiscalYear $p): array
    {
        $compensi   = (float) ($i['compensi'] ?? 0);
        $compensiRa = (float) ($i['compensi_ra'] ?? 0);
        $costi      = (float) ($i['costi'] ?? 0);
        $affitti    = (float) ($i['affitti'] ?? 0);
        $inpsDed    = (float) ($i['inps_deducibile'] ?? 0);
        $ivaAnnua   = (float) ($i['iva_annua'] ?? 0);
        $speseFam   = (float) ($i['spese_famiglia'] ?? 0);
        $imu        = (float) ($i['imu'] ?? 0);

        $reddito    = $compensi - $costi;
        $inps       = round((float) $p->aliquota_inps * min(max(0, $reddito), (float) $p->massimale_inps), 0);
        $imponibile = max(0, $reddito + (float) $p->redditi_fabbricati - $inpsDed - (float) $p->fondo_pensione);
        $irpefLorda = round(self::aScaglioni($imponibile, $p->scaglioniIrpef()), 0);
        $detrazioni = (float) $p->detrazioni;
        $irpefNetta = max(0, $irpefLorda - $detrazioni);
        $ritenute   = round((float) $p->aliquota_ritenuta * $compensiRa, 0);
        $irpefSaldo = $irpefNetta - $ritenute;
        $addReg     = round(self::aScaglioni($imponibile, $p->scaglioniAddRegionale()), 0);
        $addCom     = round((float) $p->aliquota_add_comunale * $imponibile, 0);
        $cedolare   = (float) $p->cedolare_secca;

        $totale  = $inps + $irpefNetta + $addReg + $addCom + $cedolare;
        $netto   = $reddito + $affitti - $totale;
        $accanto = $ivaAnnua + $inps + max(0, $irpefSaldo) + $addReg + $addCom + $cedolare + $imu;

        return [
            'compensi'               => $compensi,
            'compensi_ra'            => $compensiRa,
            'costi'                  => $costi,
            'affitti'                => $affitti,
            'iva_annua'              => $ivaAnnua,
            'spese_famiglia'         => $speseFam,
            'reddito'                => $reddito,
            'inps'                   => $inps,
            'inps_deducibile'        => $inpsDed,
            'imponibile_irpef'       => $imponibile,
            'irpef_lorda'            => $irpefLorda,
            'irpef_scaglioni'        => self::dettaglioScaglioni($imponibile, $p->scaglioniIrpef()),
            'redditi_fabbricati'     => (float) $p->redditi_fabbricati,
            'fondo_pensione'         => (float) $p->fondo_pensione,
            'detrazioni'             => $detrazioni,
            'irpef_netta'            => $irpefNetta,
            'ritenute'               => $ritenute,
            'irpef_saldo'            => $irpefSaldo,       // + a debito, − a credito (si regola l'anno dopo)
            'add_regionale'          => $addReg,
            'add_comunale'           => $addCom,
            'cedolare'               => $cedolare,
            'imu'                    => $imu,
            'totale_imposte'         => $totale,
            'netto'                  => $netto,
            'netto_mensile'          => round($netto / 12, 0),
            'risparmio_mensile'      => round(($netto - $speseFam) / 12, 0),
            'accantonamento_annuo'   => $accanto,
            'accantonamento_mensile' => round($accanto / 12, 0),
        ];
    }
}
