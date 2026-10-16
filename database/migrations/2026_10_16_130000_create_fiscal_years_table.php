<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parametri fiscali per anno (libero professionista in regime ordinario):
 * aliquote, scaglioni, dati della dichiarazione dell'anno precedente, crediti, fondo tasse
 * e ipotesi di proiezione modificabili (null = calcolate dai dati dell'app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('anno');

            // Aliquote e scaglioni
            $table->decimal('aliquota_inps', 6, 4)->default(0.2607);
            $table->decimal('massimale_inps', 12, 2)->default(122000);
            $table->decimal('aliquota_iva', 6, 4)->default(0.22);
            $table->decimal('aliquota_ritenuta', 6, 4)->default(0.20);
            $table->json('scaglioni_irpef')->nullable();           // [{"fino":28000,"aliquota":0.23},…,{"fino":null,"aliquota":0.43}]
            $table->json('scaglioni_add_regionale')->nullable();
            $table->decimal('aliquota_add_comunale', 6, 4)->default(0.0074);

            // Altri redditi, deduzioni e detrazioni
            $table->decimal('redditi_fabbricati', 12, 2)->default(0);
            $table->decimal('cedolare_secca', 12, 2)->default(0);
            $table->decimal('fondo_pensione', 12, 2)->default(0);
            $table->decimal('detrazioni', 12, 2)->default(0);

            // Dichiarazione dell'anno precedente (base per saldi e acconti)
            $table->decimal('prec_inps_dovuto', 12, 2)->nullable();
            $table->decimal('prec_inps_acconti', 12, 2)->nullable();
            $table->decimal('prec_irpef_netta', 12, 2)->nullable();
            $table->decimal('prec_ritenute', 12, 2)->nullable();
            $table->decimal('prec_cedolare', 12, 2)->nullable();
            $table->decimal('iva_base_acconto', 12, 2)->nullable();   // IVA dovuta 4° trimestre anno prec. (metodo storico)

            // Crediti disponibili dall'inizio dell'anno
            $table->decimal('credito_irpef_iniziale', 12, 2)->default(0);
            $table->decimal('credito_iva_iniziale', 12, 2)->default(0);

            // Fondo tasse (conto BBVA) e accantonamento obiettivo
            $table->decimal('fondo_tasse_saldo', 12, 2)->nullable();
            $table->date('fondo_tasse_data')->nullable();
            $table->decimal('accantonamento_perc', 6, 4)->default(0.30);

            // Ipotesi di proiezione (null = calcolate dai movimenti, annualizzate per l'anno in corso)
            $table->decimal('compensi_previsti', 12, 2)->nullable();
            $table->decimal('compensi_ra_previsti', 12, 2)->nullable();
            $table->decimal('costi_previsti', 12, 2)->nullable();
            $table->decimal('affitti_previsti', 12, 2)->nullable();
            $table->decimal('iva_annua_prevista', 12, 2)->nullable();
            $table->decimal('spese_famiglia_previste', 12, 2)->nullable();

            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'anno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
