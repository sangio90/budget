<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BudgetExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = \App\Models\User::first()?->id;
        if (!$userId) return;

        // Risolvi ID categorie una volta sola
        $cat = fn(string $categoria, string $nome) =>
            \App\Models\BudgetCategory::where('categoria', $categoria)->where('nome', $nome)->value('id');

        $ids = [
            'supermercato'  => $cat('SPESA',       'Supermercato'),
            'gas_ufficio'   => $cat('UFFICIO',      'GAS (Enel)'),
            'vacanze'       => $cat('VACANZE',      'Viaggi vacanze e soggiorni'),
            'salute'        => $cat('SALUTE',       'Visite/Esami/Farmaci'),
            'regali'        => $cat('EXTRA',        'Regali altri'),
            'videogiochi'   => $cat('HOBBY GUIDO',  'Videogiochi'),
            'luce_casa'     => $cat('CASA',         'Bollette Luce Octopus + Plenitude'),
            'acqua_casa'    => $cat('CASA',         'Bollette Acqua Padania Acque'),
            'affitto'       => $cat('UFFICIO',      'Affitto'),
            'luce_ufficio'  => $cat('UFFICIO',      'Luce (ENI Plenitude)'),
        ];

        // Record estratti dall'Excel (categoria_excel, anno, mese, importo, descrizione)
        $records = [
            // --- SPESA (supermercato) ---
            ['supermercato', 2026, 4,  484.67, 'Bennet'],
            ['supermercato', 2026, 3,  240.55, 'Bennet'],
            ['supermercato', 2026, 2,  550.87, 'Bennet'],
            ['supermercato', 2026, 1,  456.78, 'Bennet'],
            ['supermercato', 2025, 12, 518.62, 'Bennet'],
            ['supermercato', 2025, 11, 183.99, 'Bennet'],
            ['supermercato', 2025, 10, 182.24, 'Bennet'],
            ['supermercato', 2026, 4,  270.35, 'Famila'],
            ['supermercato', 2026, 3,  134.21, 'Famila'],
            ['supermercato', 2026, 1,   34.89, 'Famila'],
            ['supermercato', 2025, 12,  72.61, 'Famila'],
            ['supermercato', 2025, 11,  85.43, 'Famila'],
            ['supermercato', 2025, 10,  23.91, 'Famila'],
            ['supermercato', 2026, 3,  107.35, 'Banco Fresco'],
            ['supermercato', 2026, 2,   44.37, 'Banco Fresco'],
            ['supermercato', 2026, 1,  112.79, 'Banco Fresco'],
            ['supermercato', 2026, 1,   35.91, 'Ipercoop'],
            ['supermercato', 2026, 3,    3.38, 'Salumeria Stringa'],
            ['supermercato', 2026, 1,    9.60, 'Salumeria Stringa'],
            ['supermercato', 2025, 12,  10.00, 'Salumeria Stringa'],
            ['supermercato', 2026, 1,  150.00, 'Fede'],
            ['supermercato', 2026, 1,  180.00, 'Esselunga'],
            ['supermercato', 2026, 4,  118.61, 'Altro'],
            ['supermercato', 2026, 3,   14.90, 'Altro'],
            ['supermercato', 2026, 1,   52.00, 'Altro'],
            // --- USCITE UFFICIO (utenze generiche) ---
            ['gas_ufficio',  2026, 4,  312.25, 'Uscite ufficio varie'],
            ['gas_ufficio',  2026, 3,  373.16, 'Uscite ufficio varie'],
            ['gas_ufficio',  2026, 2,   78.25, 'Uscite ufficio varie'],
            ['gas_ufficio',  2026, 1,  234.14, 'Uscite ufficio varie'],
            // --- VACANZE ---
            ['vacanze', 2026, 2,  210.00, 'Siviglia - costi durante il viaggio'],
            ['vacanze', 2026, 2,  180.00, 'Siviglia - airbnb'],
            ['vacanze', 2026, 2,  231.00, 'Siviglia - volo'],
            ['vacanze', 2026, 4,   82.10, 'Siviglia - iscrizione maratona'],
            ['vacanze', 2026, 2,   92.00, 'Siviglia - iscrizione maratona'],
            ['vacanze', 2026, 2,   43.00, 'Siviglia - parcheggio'],
            ['vacanze', 2026, 7, 2332.80, 'PSG Casa'],
            ['vacanze', 2026, 2, 1117.20, 'PSG Casa'],
            ['vacanze', 2026, 3,  135.00, 'Acconto bagni Dolce Vita PSG'],
            // --- SALUTE ---
            ['salute', 2026, 5,   58.15, 'Esami sangue Fede'],
            ['salute', 2026, 4,  112.00, 'Mascherina Fede'],
            ['salute', 2026, 3,   45.00, 'Pubalgia (Luca Mombelli)'],
            ['salute', 2026, 5,   17.60, 'Parafarmacia'],
            ['salute', 2026, 4,  302.00, 'Mascherina Fede'],
            ['salute', 2026, 3,   45.00, 'Pubalgia + schiena (Luca Mombelli)'],
            ['salute', 2026, 5,   92.00, 'Mammografia Fede'],
            // --- REGALI / EXTRA ---
            ['regali', 2026, 4,  699.00, 'Mac Fede'],
            ['regali', 2026, 3,  289.20, 'Mac Fede'],
            ['regali', 2026, 2,   20.00, 'Mac Fede'],
            ['regali', 2026, 4,  600.00, 'Bici Guido'],
            ['regali', 2026, 4,   51.00, 'Decathlon - Bici Guido'],
            ['regali', 2026, 4,  515.00, 'Decathlon Online - Bici Guido'],
            ['regali', 2026, 4, 1865.00, 'Regali vari'],
            // --- VIDEOGIOCHI ---
            ['videogiochi', 2026, 3, 44.99, 'Octopath Traveler NS2'],
            // --- CASA ---
            ['luce_casa', 2026, 3, 556.55, 'Luce'],
            ['acqua_casa', 2026, 4, 118.49, 'Acqua Padania'],
            ['acqua_casa', 2026, 1, 190.77, 'Acqua Padania'],
            // --- UFFICIO ---
            ['affitto',      2026, 3, 429.47, 'Affitto'],
            ['affitto',      2026, 2, 429.47, 'Affitto'],
            ['affitto',      2026, 1, 429.47, 'Affitto'],
            ['luce_ufficio', 2026, 2, 104.91, 'Luce ufficio'],
            ['gas_ufficio',  2026, 4, 140.00, 'Altro ufficio'],
        ];

        foreach ($records as [$key, $anno, $mese, $importo, $note]) {
            $categoryId = $ids[$key] ?? null;
            if (!$categoryId) {
                echo "SKIP: chiave '$key' non trovata\n";
                continue;
            }
            // Usa l'ultimo giorno del mese come data
            $data = \Carbon\Carbon::create($anno, $mese, 1)->endOfMonth()->format('Y-m-d');

            \App\Models\BudgetExpense::create([
                'user_id'            => $userId,
                'budget_category_id' => $categoryId,
                'importo'            => $importo,
                'data'               => $data,
                'note'               => $note,
            ]);
        }

        echo "Importate " . count($records) . " spese budget.\n";
    }
}
