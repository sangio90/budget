<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    protected $fillable = ['categoria', 'nome', 'importo_annuale', 'importo_mensile', 'periodo', 'note', 'sort_order'];

    public function expenses()
    {
        return $this->hasMany(BudgetExpense::class);
    }

    public function amounts()
    {
        return $this->hasMany(BudgetAmount::class);
    }

    /** Restituisce importo_annuale e importo_mensile per l'anno richiesto (override o default). */
    public function importiPerAnno(int $anno): array
    {
        $override = $this->amounts->firstWhere('anno', $anno);
        if ($override) {
            return ['annuale' => (float) $override->importo_annuale, 'mensile' => (float) $override->importo_mensile];
        }
        return ['annuale' => (float) $this->importo_annuale, 'mensile' => (float) $this->importo_mensile];
    }

    public function speseMese(int $anno, int $mese): float
    {
        return $this->expenses()
            ->whereYear('data', $anno)
            ->whereMonth('data', $mese)
            ->sum('importo');
    }

    public function speseAnno(int $anno): float
    {
        return $this->expenses()
            ->whereYear('data', $anno)
            ->sum('importo');
    }
}
