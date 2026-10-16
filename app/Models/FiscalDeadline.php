<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalDeadline extends Model
{
    public const TRIBUTI = [
        'IVA', 'INPS', 'IRPEF', 'Addizionale regionale', 'Addizionale comunale', 'Cedolare secca', 'IMU', 'Altro',
    ];

    public const STATI = [
        'stimato'    => 'Stimato',
        'da_pagare'  => 'Da pagare',
        'pagato'     => 'Pagato',
        'compensato' => 'Compensato',
    ];

    /** Tributi compensabili con il credito IRPEF (come nell'Excel: tutto tranne IVA, IMU e altro). */
    public const COMPENSABILI_IRPEF = ['INPS', 'IRPEF', 'Addizionale regionale', 'Addizionale comunale', 'Cedolare secca'];

    protected $fillable = [
        'user_id', 'scadenza', 'tributo', 'descrizione', 'anno_competenza', 'importo_previsto', 'compensato',
        'da_versare', 'importo_pagato', 'data_pagamento', 'stato', 'transaction_id', 'chiave', 'manuale', 'ordine', 'note',
    ];

    protected $casts = [
        'scadenza'       => 'date',
        'data_pagamento' => 'date',
        'manuale'        => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Pagata o compensata: non concorre più a quanto c'è ancora da versare. */
    public function chiusa(): bool
    {
        return in_array($this->stato, ['pagato', 'compensato'], true);
    }
}
