<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = \App\Models\User::first()?->id;
        if (!$userId) return;

        $transactions = [
            // 2026 uscite
            ['tipo' => 'uscita', 'data' => '2026-01-31', 'importo' => 2240, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-02-02', 'importo' => 4486, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-02-28', 'importo' => 2448, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-02-28', 'importo' => 3100, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-03-31', 'importo' => 912, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-03-31', 'importo' => 1848, 'causale' => 'Umberto', 'note' => 'fatturati e pagati da diego'],
            ['tipo' => 'uscita', 'data' => '2026-04-01', 'importo' => 4050, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-04-30', 'importo' => 2288, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2026-05-03', 'importo' => 3325, 'causale' => 'Emanuele', 'note' => null],
            // 2026 f24
            ['tipo' => 'f24', 'data' => '2026-04-30', 'importo' => 111.86, 'causale' => 'commercialista', 'note' => null],
            ['tipo' => 'f24', 'data' => '2026-05-18', 'importo' => 108.00, 'causale' => 'commercialista', 'note' => null],
            ['tipo' => 'f24', 'data' => '2026-05-18', 'importo' => 9647.13, 'causale' => 'iva 1 trimestre', 'note' => null],
            // 2026 entrate
            ['tipo' => 'entrata', 'data' => '2026-01-13', 'importo' => 1583.04, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-01-13', 'importo' => 3215.04, 'causale' => 'Italpacking', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-01-23', 'importo' => 5600.00, 'causale' => 'Apir', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-01-30', 'importo' => 4569.60, 'causale' => 'ID3', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-01-30', 'importo' => 3182.40, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-02-04', 'importo' => 2987.80, 'causale' => 'EAW', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-02-10', 'importo' => 2827.44, 'causale' => 'Italpacking', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-02-13', 'importo' => 1456.56, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-02-17', 'importo' => 1377.00, 'causale' => 'Vetraco', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-02-27', 'importo' => 4569.60, 'causale' => 'ID3', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-02', 'importo' => 3341.52, 'causale' => 'Diego', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-03', 'importo' => 941.99, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-04', 'importo' => 1421.47, 'causale' => 'Dibotek', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-11', 'importo' => 1627.92, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-11', 'importo' => 3170.16, 'causale' => 'Italpacking', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-16', 'importo' => 8424.50, 'causale' => 'Apir', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-31', 'importo' => 891.07, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-31', 'importo' => 9180.00, 'causale' => 'Prada', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2026-03-31', 'importo' => 5222.40, 'causale' => 'ID3', 'note' => null],
            // 2025 uscite (subset)
            ['tipo' => 'uscita', 'data' => '2025-01-07', 'importo' => 1568, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-01-14', 'importo' => 2325, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-02-03', 'importo' => 3140, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-02-04', 'importo' => 2352, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-02-28', 'importo' => 3375, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-02-28', 'importo' => 2272, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-04-01', 'importo' => 752, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-04-02', 'importo' => 3693, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-05-02', 'importo' => 3250, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-05-02', 'importo' => 2560, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-06-30', 'importo' => 2800, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-06-03', 'importo' => 1984, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-07-01', 'importo' => 2875, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-07-02', 'importo' => 2278, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-07-31', 'importo' => 2675, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-08-05', 'importo' => 2416, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-08-01', 'importo' => 1750, 'causale' => 'Emanuele', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-09-01', 'importo' => 1192, 'causale' => 'Umberto', 'note' => null],
            ['tipo' => 'uscita', 'data' => '2025-09-30', 'importo' => 3525, 'causale' => 'Emanuele', 'note' => null],
            // 2025 f24
            ['tipo' => 'f24', 'data' => '2025-01-16', 'importo' => 114.00, 'causale' => 'commercialista', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-03-17', 'importo' => 3352.00, 'causale' => 'saldo iva 4 trimestre 2024', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-05-16', 'importo' => 9942.36, 'causale' => 'iva 1 trimestre', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-08-20', 'importo' => 114.00, 'causale' => 'commercialista', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-08-26', 'importo' => 8590.30, 'causale' => 'iva 2 trimestre', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-10-16', 'importo' => 137.00, 'causale' => 'commercialista', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-11-07', 'importo' => 6240.31, 'causale' => 'iva 3 trimestre', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-12-01', 'importo' => 3538.67, 'causale' => '2 acconto imposte 2025', 'note' => null],
            ['tipo' => 'f24', 'data' => '2025-12-03', 'importo' => 10330.14, 'causale' => 'acconto iva 4 trimestre', 'note' => null],
            // 2025 entrate (subset)
            ['tipo' => 'entrata', 'data' => '2025-01-13', 'importo' => 6083.28, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-01-14', 'importo' => 4492.48, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-01-21', 'importo' => 2937.60, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-02-04', 'importo' => 1930.66, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-02-14', 'importo' => 4883.76, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-02-28', 'importo' => 2569.60, 'causale' => 'ID3', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-03-03', 'importo' => 4987.70, 'causale' => 'EAW', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-03-07', 'importo' => 2524.70, 'causale' => 'BRV', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-03-11', 'importo' => 5826.24, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-03-17', 'importo' => 9180.00, 'causale' => 'Prada', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-03-31', 'importo' => 5222.40, 'causale' => 'ID3', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-04-11', 'importo' => 5055.12, 'causale' => 'ILT', 'note' => null],
            ['tipo' => 'entrata', 'data' => '2025-04-30', 'importo' => 5548.80, 'causale' => 'ID3', 'note' => null],
        ];

        foreach ($transactions as $t) {
            \App\Models\Transaction::create(['user_id' => $userId] + $t);
        }
    }
}
