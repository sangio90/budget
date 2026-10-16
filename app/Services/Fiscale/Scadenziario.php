<?php

namespace App\Services\Fiscale;

use App\Models\FiscalDeadline;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Scadenziario fiscale con i crediti scalati riga per riga, come la colonna
 * "Credito IRPEF residuo" dell'Excel:
 *  - credito IRPEF (da dichiarazione) → INPS, IRPEF, addizionali, cedolare
 *  - credito IVA → versamenti IVA
 * Il credito iniziale di un anno (parametri) si aggiunge alla prima scadenza di quell'anno;
 * una riga con importo negativo (es. saldo IRPEF a credito) aumenta il credito disponibile.
 * Righe con "compensato" impostato usano quel valore; le altre prendono min(importo, credito residuo).
 */
class Scadenziario
{
    /** Soglia oltre la quale una data di scadenza è considerata "grossa". */
    public const SOGLIA_SCADENZA_GROSSA = 1000;

    /**
     * Tutte le scadenze dell'utente in ordine, ognuna con: compensato_calc, da_versare_calc,
     * residuo_irpef, residuo_iva (credito rimasto dopo la riga).
     */
    public function righe(int $userId): Collection
    {
        $crediti = FiscalYear::where('user_id', $userId)->orderBy('anno')->get()
            ->mapWithKeys(fn($p) => [(int) $p->anno => [(float) $p->credito_irpef_iniziale, (float) $p->credito_iva_iniziale]]);

        $righe = FiscalDeadline::with('transaction')->where('user_id', $userId)->get()
            ->sort(fn($a, $b) => [$a->scadenza->toDateString(), (float) $a->importo_previsto < 0 ? 0 : 1, $a->ordine, $a->id]
                <=> [$b->scadenza->toDateString(), (float) $b->importo_previsto < 0 ? 0 : 1, $b->ordine, $b->id])
            ->values();

        $pool       = ['irpef' => 0.0, 'iva' => 0.0];
        $anniCredit = $crediti->keys()->all();

        foreach ($righe as $r) {
            // Crediti iniziali degli anni fino a quello della scadenza
            while ($anniCredit && $anniCredit[0] <= $r->scadenza->year) {
                $a = array_shift($anniCredit);
                $pool['irpef'] += $crediti[$a][0];
                $pool['iva']   += $crediti[$a][1];
            }

            $tipo    = $r->tributo === 'IVA' ? 'iva' : (in_array($r->tributo, FiscalDeadline::COMPENSABILI_IRPEF, true) ? 'irpef' : null);
            $importo = (float) $r->importo_previsto;

            if ($importo < 0) {
                if ($tipo) $pool[$tipo] += -$importo;
                $comp = 0.0;
            } elseif ($r->compensato !== null) {
                $comp = (float) $r->compensato;
                if ($tipo) $pool[$tipo] = max(0, $pool[$tipo] - $comp);
            } elseif ($tipo && ! $r->chiusa()) {
                $comp = round(min($importo, $pool[$tipo]), 2);
                $pool[$tipo] -= $comp;
            } else {
                $comp = 0.0;
            }

            $r->compensato_calc = $comp;
            $r->da_versare_calc = round(max(0, $importo - $comp), 2);
            $r->residuo_irpef   = round($pool['irpef'], 2);
            $r->residuo_iva     = round($pool['iva'], 2);
        }

        return $righe;
    }

    /** Salva il "da versare" calcolato sulle righe aperte (informativo, per le query esterne). */
    public function aggiornaDaVersare(int $userId): void
    {
        foreach ($this->righe($userId) as $r) {
            if ((float) $r->da_versare !== $r->da_versare_calc || $r->da_versare === null) {
                FiscalDeadline::whereKey($r->id)->update(['da_versare' => $r->da_versare_calc]);
            }
        }
    }

    /** Totali per data di scadenza (importo, compensato, da versare). */
    public function totaliPerData(Collection $righe): Collection
    {
        return $righe->groupBy(fn($r) => $r->scadenza->toDateString())
            ->map(fn($g, $data) => [
                'data'       => Carbon::parse($data),
                'importo'    => round($g->sum(fn($r) => max(0, (float) $r->importo_previsto)), 2),
                'compensato' => round($g->sum('compensato_calc'), 2),
                'da_versare' => round($g->sum('da_versare_calc'), 2),
                'aperte'     => $g->reject->chiusa()->count(),
            ]);
    }

    /**
     * "Quanto dovresti avere da parte": da versare (righe non pagate) dalla data indicata
     *  - fino alla prossima scadenza grossa (≥ SOGLIA_SCADENZA_GROSSA)
     *  - fino al 30 giugno dell'anno successivo
     */
    public function fondoTasse(int $userId, CarbonInterface $oggi, ?Collection $righe = null): array
    {
        $righe  = $righe ?? $this->righe($userId);
        $aperte = $righe->reject->chiusa()->filter(fn($r) => $r->scadenza->gte($oggi->copy()->startOfDay()));
        $scadute = $righe->reject->chiusa()
            ->filter(fn($r) => $r->scadenza->lt($oggi->copy()->startOfDay()) && $r->da_versare_calc > 0);

        $perData = $aperte->groupBy(fn($r) => $r->scadenza->toDateString())
            ->map(fn($g) => $g->sum('da_versare_calc'))->sortKeys();
        $prossimaGrossa = $perData->filter(fn($v) => $v >= self::SOGLIA_SCADENZA_GROSSA)->keys()->first();

        $giugno = Carbon::create($oggi->year + 1, 6, 30)->endOfDay();
        $sommaFino = fn(?string $data) => $data === null ? 0.0
            : round($aperte->filter(fn($r) => $r->scadenza->lte(Carbon::parse($data)->endOfDay()))->sum('da_versare_calc'), 2);

        $fino1 = $sommaFino($prossimaGrossa);
        $fino2 = $sommaFino($giugno->toDateString());

        return [
            'prossima_grossa'       => $prossimaGrossa ? Carbon::parse($prossimaGrossa) : null,
            'fino_prossima_grossa'  => $fino1,
            'giugno'                => $giugno->copy()->startOfDay(),
            'fino_giugno'           => $fino2,
            'scadute_non_pagate'    => $scadute->count(),
            'anni_coperti'          => $aperte->map(fn($r) => $r->scadenza->year)->unique()->sort()->values()->all(),
        ];
    }
}
