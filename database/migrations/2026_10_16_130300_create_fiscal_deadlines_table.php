<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scadenziario fiscale (IVA, INPS, IRPEF, addizionali, cedolare, IMU…).
 * Le righe "stimato" non modificate a mano vengono ricalcolate da "Genera stime".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('scadenza')->index();
            $table->string('tributo');                       // IVA, INPS, IRPEF, Addizionale regionale, …
            $table->string('descrizione');
            $table->unsignedSmallInteger('anno_competenza');
            $table->decimal('importo_previsto', 12, 2);      // negativo = credito che matura (es. saldo IRPEF a credito)
            $table->decimal('compensato', 12, 2)->nullable(); // null = calcolato scalando il credito disponibile
            $table->decimal('da_versare', 12, 2)->nullable(); // ultimo valore calcolato (informativo)
            $table->decimal('importo_pagato', 12, 2)->nullable();
            $table->date('data_pagamento')->nullable();
            $table->string('stato')->default('stimato');     // stimato, da_pagare, pagato, compensato
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('chiave')->nullable();            // identificativo delle righe generate (es. iva:q3:2026)
            $table->boolean('manuale')->default(false);      // modificata a mano: "Genera stime" non la tocca
            $table->unsignedSmallInteger('ordine')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'chiave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_deadlines');
    }
};
