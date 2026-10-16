<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regole di fatturazione per cliente (causale delle transactions di tipo entrata):
 * imponibile = incasso / (1 + IVA − ritenuta). Default 22% / 20% → fattore 1,02.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('causale');
            $table->decimal('aliquota_iva', 6, 4)->default(0.22);
            $table->decimal('aliquota_ritenuta', 6, 4)->default(0.20);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'causale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_clients');
    }
};
