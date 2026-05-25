<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'tipo', 'importo', 'data', 'causale', 'note'];

    protected $casts = ['data' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
