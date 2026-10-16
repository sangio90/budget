<?php

namespace App\Services\Fiscale;

use App\Models\FiscalDeadline;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Proiezione annua di imposte e netto (foglio "Proiezione 2026-2028").
 *  - anni passati:  consuntivo dell'intero anno
 *  - anno in corso: consuntivo a oggi + media degli ultimi 3 mesi × mesi che mancano
 *  - anni futuri:   stesse ipotesi dell'anno in corso
 * Ogni ipotesi può essere fissata a mano nei parametri dell'anno (campi *_previsti).
 * L'INPS deducibile è quello versato per cassa nell'anno: saldo dell'anno prima + acconti (80%).
 */
class ProiezioneFiscale
{
    private CarbonInterface $oggi;
    private array $cacheIpotesi = [];
    private array $cacheRisultati = [];
    private array $cacheParametri = [];
    private array $primoAnno = [];

    public function __construct(
        private DatiFiscali $dati,
        private CalcoloImposte $calcolo,
        ?CarbonInterface $oggi = null,
    ) {
        $this->oggi = $oggi ? Carbon::instance($oggi) : Carbon::today();
    }

    public function oggi(): CarbonInterface
    {
        return $this->oggi;
    }

    public function parametri(int $userId, int $anno): FiscalYear
    {
        return $this->cacheParametri[$userId][$anno] ??= FiscalYear::perAnno($userId, $anno);
    }

    /** Mesi trascorsi dell'anno (frazionari: al 16/10 sono ~9,5). */
    public function mesiTrascorsi(int $anno): float
    {
        if ($anno < $this->oggi->year) return 12.0;
        if ($anno > $this->oggi->year) return 0.0;

        return round($this->oggi->dayOfYear / $this->oggi->daysInYear * 12, 2);
    }

    /** Mesi trascorsi dall'inizio dell'anno fino alla data indicata (frazionari). */
    private function mesiTrascorsiAl(CarbonInterface $data): float
    {
        return round($data->dayOfYear / $data->daysInYear * 12, 2);
    }

    /**
     * Ipotesi annue di un anno, con l'indicazione della fonte di ogni valore
     * (consuntivo, a oggi + ritmo ultimi 3 mesi, come anno in corso, impostato a mano).
     */
    public function ipotesi(int $userId, int $anno): array
    {
        if (isset($this->cacheIpotesi[$userId][$anno])) {
            return $this->cacheIpotesi[$userId][$anno];
        }

        $p         = $this->parametri($userId, $anno);
        $annoOggi  = $this->oggi->year;
        $consuntivo = null;

        if ($anno > $annoOggi) {
            $base  = $this->ipotesi($userId, $annoOggi)['valori'];
            $fonte = array_fill_keys(array_keys($base), 'come ' . $annoOggi);
        } else {
            $inCorso    = $anno === $annoOggi;
            $consuntivo = $this->dati->consuntivo($userId, $anno, $inCorso ? $this->oggi : null);
            $mesi       = $this->mesiTrascorsi($anno);
            $etichetta  = $inCorso ? 'a oggi + ritmo ultimi 3 mesi' : 'consuntivo';

            // Anno in corso: valore a oggi + media degli ultimi 3 mesi × mesi che mancano.
            // Segue i cambi di ritmo (un collaboratore che smette, un cliente nuovo) meglio della media dell'anno.
            $chiavi = ['compensi' => 'imponibile', 'compensi_ra' => 'compensi_ra', 'costi' => 'costi',
                       'affitti' => 'affitti', 'iva_incassata' => 'iva_incassata', 'spese_famiglia' => 'spese_famiglia'];
            $mancano = $inCorso ? max(0, 12 - $mesi) : 0;
            $recente = null;
            $mesiRecenti = 0.0;
            if ($inCorso && $mancano > 0) {
                $inizio = $this->oggi->copy()->subMonthsNoOverflow(3);
                if ($inizio->year < $anno) {
                    $inizio = Carbon::create($anno, 1, 1);
                }
                $prima       = $this->dati->consuntivo($userId, $anno, $inizio->copy()->subDay());
                $mesiRecenti = max(0.01, $mesi - $this->mesiTrascorsiAl($inizio->copy()->subDay()));
                $recente     = array_map(fn($c) => ($consuntivo[$c] ?? 0) - ($prima[$c] ?? 0), $chiavi);
            }
            $base = [];
            foreach ($chiavi as $chiave => $campo) {
                $aOggi = (float) ($consuntivo[$campo] ?? 0);
                $base[$chiave] = round($recente === null ? $aOggi : $aOggi + $recente[$chiave] / $mesiRecenti * $mancano, 0);
            }
            $fonte = array_fill_keys(array_keys($base), $etichetta);

            // IVA dell'anno: dalle scadenze IVA di competenza (versate + stimate), altrimenti IVA incassata
            $ivaScadenze = FiscalDeadline::where('user_id', $userId)->where('tributo', 'IVA')
                ->where('anno_competenza', $anno)->get();
            if ($ivaScadenze->isNotEmpty()) {
                $base['iva_annua']  = round((float) $ivaScadenze->sum('importo_previsto'), 0);
                $fonte['iva_annua'] = 'scadenziario';
            } else {
                $base['iva_annua']  = $base['iva_incassata'];
                $fonte['iva_annua'] = $etichetta . ' (IVA incassata)';
            }
        }

        $override = [
            'compensi'       => 'compensi_previsti',
            'compensi_ra'    => 'compensi_ra_previsti',
            'costi'          => 'costi_previsti',
            'affitti'        => 'affitti_previsti',
            'iva_annua'      => 'iva_annua_prevista',
            'spese_famiglia' => 'spese_famiglia_previste',
        ];
        foreach ($override as $chiave => $campo) {
            if ($p->exists && $p->num($campo) !== null) {
                $base[$chiave]  = $p->num($campo);
                $fonte[$chiave] = 'impostato';
            }
        }

        return $this->cacheIpotesi[$userId][$anno] = [
            'valori'     => $base,
            'fonte'      => $fonte,
            'consuntivo' => $consuntivo,
            'mesi'       => $this->mesiTrascorsi($anno),
        ];
    }

    /** Risultato completo di un anno di competenza (vedi CalcoloImposte). */
    public function risultato(int $userId, int $anno): array
    {
        if (isset($this->cacheRisultati[$userId][$anno])) {
            return $this->cacheRisultati[$userId][$anno];
        }

        $valori = $this->ipotesi($userId, $anno)['valori'];
        $valori['inps_deducibile'] = $this->inpsDeducibile($userId, $anno);

        return $this->cacheRisultati[$userId][$anno] = $this->calcolo->calcola($valori, $this->parametri($userId, $anno));
    }

    /** Proiezione di più anni consecutivi (default: anno indicato e i due successivi). */
    public function triennio(int $userId, int $anno, int $anni = 3): array
    {
        $out = [];
        for ($a = $anno; $a < $anno + $anni; $a++) {
            $out[$a] = $this->risultato($userId, $a);
        }

        return $out;
    }

    /** INPS di competenza calcolato (indipendente dall'INPS deducibile). */
    public function inpsDovuto(int $userId, int $anno): float
    {
        $v = $this->ipotesi($userId, $anno)['valori'];
        $p = $this->parametri($userId, $anno);

        return round((float) $p->aliquota_inps * min(max(0, $v['compensi'] - $v['costi']), (float) $p->massimale_inps), 0);
    }

    /** INPS "dichiarato" per un anno: dato della dichiarazione (salvato nei parametri dell'anno dopo) o calcolato. */
    public function inpsDichiarato(int $userId, int $anno): float
    {
        return $this->parametri($userId, $anno + 1)->num('prec_inps_dovuto') ?? $this->inpsDovuto($userId, $anno);
    }

    /** Acconti INPS versati per un anno: dato della dichiarazione o 80% del dichiarato dell'anno prima. */
    public function accontiInpsVersati(int $userId, int $anno): float
    {
        $dichiarati = $this->parametri($userId, $anno + 1)->num('prec_inps_acconti');
        if ($dichiarati !== null) {
            return $dichiarati;
        }

        return $anno <= $this->primoAnno($userId)
            ? 0.8 * $this->inpsDovuto($userId, $anno)        // prima dei dati disponibili: regime costante
            : 0.8 * $this->inpsDichiarato($userId, $anno - 1);
    }

    /** INPS versato nell'anno (deducibile per cassa) = saldo dell'anno prima + acconti dell'anno (80%). */
    public function inpsDeducibile(int $userId, int $anno): float
    {
        $p = $this->parametri($userId, $anno);
        if ($anno - 1 < $this->primoAnno($userId) && $p->num('prec_inps_dovuto') === null) {
            return $this->inpsDovuto($userId, $anno);        // nessun dato sull'anno prima: regime costante
        }

        $dichPrec = $this->inpsDichiarato($userId, $anno - 1);

        return round($dichPrec - $this->accontiInpsVersati($userId, $anno - 1) + 0.8 * $dichPrec, 2);
    }

    /** Primo anno con parametri salvati (sotto non si risale la catena INPS). */
    private function primoAnno(int $userId): int
    {
        return $this->primoAnno[$userId] ??= (int) (FiscalYear::where('user_id', $userId)->min('anno') ?? $this->oggi->year);
    }
}
