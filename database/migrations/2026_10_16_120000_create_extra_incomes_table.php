<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrate extra (affitti, fotovoltaico GSE, cedole, interessi, rimborsi spese immobili):
 * separate dalle transactions (partita IVA) e dalle budget_expenses (spese familiari).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('categoria');           // Affitto, Rimborso spese immobile, Fotovoltaico (GSE), Cedole, Interessi conto
            $table->string('immobile')->nullable(); // Agnadello, Ombriano, Bagnolo, Castione, ...
            $table->decimal('importo', 10, 2);
            $table->date('data')->index();
            $table->string('causale');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_incomes');
    }
};
