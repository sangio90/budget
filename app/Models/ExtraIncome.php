<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraIncome extends Model
{
    /** Categorie proposte nel form (se ne possono inserire anche di nuove). */
    public const CATEGORIE = [
        'Affitto',
        'Rimborso spese immobile',
        'Fotovoltaico (GSE)',
        'Cedole',
        'Interessi conto',
    ];

    /** Immobili proposti nel form (oltre a quelli già usati). */
    public const IMMOBILI = ['Agnadello', 'Ombriano', 'Bagnolo', 'Castione'];

    protected $fillable = ['user_id', 'categoria', 'immobile', 'importo', 'data', 'causale', 'note'];

    protected $casts = ['data' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Riepilogo annuale delle entrate extra di un utente: totale, per mese, per categoria,
     * per immobile e matrice categoria × mese. Aggregazione in PHP (evita MONTH(),
     * che non esiste in SQLite).
     */
    public static function riepilogoAnno(int $userId, int $anno): array
    {
        $righe = static::where('user_id', $userId)
            ->whereYear('data', $anno)
            ->get(['categoria', 'immobile', 'data', 'importo']);

        $perMese       = array_fill(1, 12, 0.0);
        $perCategoria  = [];
        $perImmobile   = [];
        $mesiCategoria = [];

        foreach ($righe as $r) {
            $m   = $r->data->month;
            $imp = (float) $r->importo;

            $perMese[$m] += $imp;
            $perCategoria[$r->categoria] = ($perCategoria[$r->categoria] ?? 0) + $imp;
            $mesiCategoria[$r->categoria] ??= array_fill(1, 12, 0.0);
            $mesiCategoria[$r->categoria][$m] += $imp;
            if ($r->immobile) {
                $perImmobile[$r->immobile] = ($perImmobile[$r->immobile] ?? 0) + $imp;
            }
        }

        $arrotonda = fn(array $a) => array_map(fn($v) => round($v, 2), $a);
        arsort($perCategoria);
        arsort($perImmobile);
        $mesiCategoria = array_map($arrotonda, array_replace(array_flip(array_keys($perCategoria)), $mesiCategoria));

        // Per l'anno in corso la media considera solo i mesi trascorsi
        $mesiTrascorsi = $anno === now()->year ? now()->month : 12;
        $totale        = round(array_sum($perMese), 2);

        return [
            'totale'        => $totale,
            'media'         => $mesiTrascorsi > 0 ? round($totale / $mesiTrascorsi, 2) : 0,
            'mesiTrascorsi' => $mesiTrascorsi,
            'conteggio'     => $righe->count(),
            'perMese'       => $arrotonda($perMese),
            'perCategoria'  => $arrotonda($perCategoria),
            'perImmobile'   => $arrotonda($perImmobile),
            'mesiCategoria' => $mesiCategoria,
        ];
    }
}
