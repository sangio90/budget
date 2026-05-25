<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetExpense extends Model
{
    protected $fillable = ['user_id', 'budget_category_id', 'importo', 'data', 'note'];

    protected $casts = ['data' => 'date'];

    public function category()
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
