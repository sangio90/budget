<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Raccoglie sotto GIORGIO le voci di SCUOLA GIORGIO (Asilo, Mensa, Grest) e
 * "Sport Giorgio" di SPORT (rinominata "Sport"). Le macrocategorie SCUOLA GIORGIO
 * e SPORT spariscono da sole, perché non restano voci con quel nome.
 * Le voci sono individuate per categoria + nome; le spese restano collegate
 * perché cambia solo la riga della voce, non il suo id.
 */
return new class extends Migration
{
    // [categoria origine, nome origine, nome nuovo, sort_order nuovo, sort_order originale]
    private const SPOSTAMENTI = [
        ['SCUOLA GIORGIO', 'Asilo',         'Asilo', 2, 25],
        ['SCUOLA GIORGIO', 'Mensa',         'Mensa', 3, 27],
        ['SCUOLA GIORGIO', 'Grest',         'Grest', 4, 26],
        ['SPORT',          'Sport Giorgio', 'Sport', 5, 1],
    ];

    public function up(): void
    {
        foreach (self::SPOSTAMENTI as [$categoria, $nome, $nuovoNome, $sort]) {
            DB::table('budget_categories')
                ->where('categoria', $categoria)
                ->where('nome', $nome)
                ->update(['categoria' => 'GIORGIO', 'nome' => $nuovoNome, 'sort_order' => $sort]);
        }
    }

    public function down(): void
    {
        foreach (self::SPOSTAMENTI as [$categoria, $nome, $nuovoNome, , $sortOriginale]) {
            DB::table('budget_categories')
                ->where('categoria', 'GIORGIO')
                ->where('nome', $nuovoNome)
                ->update(['categoria' => $categoria, 'nome' => $nome, 'sort_order' => $sortOriginale]);
        }
    }
};
