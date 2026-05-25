<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('budget_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('anno');
            $table->decimal('importo_annuale', 10, 2);
            $table->decimal('importo_mensile', 10, 2);
            $table->timestamps();
            $table->unique(['budget_category_id', 'anno']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_amounts');
    }
};
