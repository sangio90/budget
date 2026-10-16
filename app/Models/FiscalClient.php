<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalClient extends Model
{
    protected $fillable = ['user_id', 'causale', 'aliquota_iva', 'aliquota_ritenuta', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Incasso = imponibile × fattore (es. 1 + 22% IVA − 20% ritenuta = 1,02). */
    public function fattore(): float
    {
        return 1 + (float) $this->aliquota_iva - (float) $this->aliquota_ritenuta;
    }
}
