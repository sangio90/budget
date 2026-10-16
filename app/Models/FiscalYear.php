<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    /** Scaglioni IRPEF 2026 (23/33/43%). */
    public const SCAGLIONI_IRPEF = [
        ['fino' => 28000, 'aliquota' => 0.23],
        ['fino' => 50000, 'aliquota' => 0.33],
        ['fino' => null,  'aliquota' => 0.43],
    ];

    /** Addizionale regionale Lombardia. */
    public const SCAGLIONI_ADD_REGIONALE = [
        ['fino' => 15000, 'aliquota' => 0.0123],
        ['fino' => 28000, 'aliquota' => 0.0158],
        ['fino' => 50000, 'aliquota' => 0.0172],
        ['fino' => null,  'aliquota' => 0.0173],
    ];

    /** Campi che un anno senza parametri eredita dall'anno precedente più vicino. */
    public const EREDITABILI = [
        'aliquota_inps', 'massimale_inps', 'aliquota_iva', 'aliquota_ritenuta', 'scaglioni_irpef',
        'scaglioni_add_regionale', 'aliquota_add_comunale', 'redditi_fabbricati', 'cedolare_secca',
        'fondo_pensione', 'detrazioni', 'accantonamento_perc',
    ];

    /** Campi importo del form parametri (oltre ad anno, scaglioni e date). */
    public const CAMPI_NUMERICI = [
        'aliquota_inps', 'massimale_inps', 'aliquota_iva', 'aliquota_ritenuta', 'aliquota_add_comunale',
        'redditi_fabbricati', 'cedolare_secca', 'fondo_pensione', 'detrazioni',
        'prec_inps_dovuto', 'prec_inps_acconti', 'prec_irpef_netta', 'prec_ritenute', 'prec_cedolare', 'iva_base_acconto',
        'credito_irpef_iniziale', 'credito_iva_iniziale', 'fondo_tasse_saldo', 'accantonamento_perc',
        'compensi_previsti', 'compensi_ra_previsti', 'costi_previsti', 'affitti_previsti', 'iva_annua_prevista',
        'spese_famiglia_previste',
    ];

    protected $fillable = [
        'user_id', 'anno', 'scaglioni_irpef', 'scaglioni_add_regionale', 'fondo_tasse_data', 'note',
        ...self::CAMPI_NUMERICI,
    ];

    protected $attributes = [
        'aliquota_inps'          => 0.2607,
        'massimale_inps'         => 122000,
        'aliquota_iva'           => 0.22,
        'aliquota_ritenuta'      => 0.20,
        'aliquota_add_comunale'  => 0.0074,
        'redditi_fabbricati'     => 0,
        'cedolare_secca'         => 0,
        'fondo_pensione'         => 0,
        'detrazioni'             => 0,
        'credito_irpef_iniziale' => 0,
        'credito_iva_iniziale'   => 0,
        'accantonamento_perc'    => 0.30,
    ];

    protected $casts = [
        'scaglioni_irpef'         => 'array',
        'scaglioni_add_regionale' => 'array',
        'fondo_tasse_data'        => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scaglioniIrpef(): array
    {
        return $this->scaglioni_irpef ?: self::SCAGLIONI_IRPEF;
    }

    public function scaglioniAddRegionale(): array
    {
        return $this->scaglioni_add_regionale ?: self::SCAGLIONI_ADD_REGIONALE;
    }

    /** Valore numerico di un campo (null se non impostato). */
    public function num(string $campo): ?float
    {
        $v = $this->getAttribute($campo);
        return $v === null || $v === '' ? null : (float) $v;
    }

    /**
     * Parametri dell'anno: quelli salvati oppure, se mancano, un record non salvato che eredita
     * aliquote e importi ricorrenti dall'anno precedente più vicino (o i default).
     */
    public static function perAnno(int $userId, int $anno): self
    {
        $salvato = static::where('user_id', $userId)->where('anno', $anno)->first();
        if ($salvato) {
            return $salvato;
        }

        $nuovo = new static(['user_id' => $userId, 'anno' => $anno]);
        $base  = static::where('user_id', $userId)->where('anno', '<', $anno)->orderByDesc('anno')->first()
            ?? static::where('user_id', $userId)->orderBy('anno')->first();
        if ($base) {
            foreach (self::EREDITABILI as $campo) {
                $nuovo->setAttribute($campo, $base->getAttribute($campo));
            }
        }

        return $nuovo;
    }
}
