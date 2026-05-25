<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAmount extends Model
{
    protected $fillable = ['budget_category_id', 'anno', 'importo_annuale', 'importo_mensile'];

    public function category()
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }
}
