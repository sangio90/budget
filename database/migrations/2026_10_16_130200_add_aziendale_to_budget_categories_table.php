<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flag "aziendale": le spese di queste voci sono costi deducibili dell'attività (pagina Fiscale).
 * Inizialmente: macrocategorie UFFICIO e LAVORO (affitto e utenze ufficio, commercialista, software, acquisti).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_categories', function (Blueprint $table) {
            $table->boolean('aziendale')->default(false)->after('nome');
        });

        DB::table('budget_categories')
            ->whereIn('categoria', ['UFFICIO', 'LAVORO'])
            ->update(['aziendale' => true]);
    }

    public function down(): void
    {
        Schema::table('budget_categories', function (Blueprint $table) {
            $table->dropColumn('aziendale');
        });
    }
};
