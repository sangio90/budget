<?php

namespace Database\Seeders;

use App\Models\ExtraIncome;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Entrate extra 2026 (1/1 – 16/10) estratte dai movimenti bancari
 * (fonte: fambudget_entrate_extra.sql). Idempotente: un record già presente
 * (stesso utente, data, categoria, immobile, importo e causale) non viene duplicato.
 *
 *   php artisan db:seed --class=ExtraIncomesSeeder
 */
class ExtraIncomesSeeder extends Seeder
{
    private const USER_ID = 1;

    public function run(): void
    {
        $userId = User::whereKey(self::USER_ID)->value('id') ?? User::first()?->id;
        if (!$userId) {
            $this->command?->warn('Nessun utente trovato: entrate extra non importate.');
            return;
        }

        // [data, categoria, immobile, importo, causale, note]
        $records = [
            ['2026-01-05', 'Interessi conto', null, 2.73, 'BBVA conto deposito', 'ACCREDITO  1,50% A FRONTE DEL SALDO MENSILE'],
            ['2026-01-13', 'Affitto', 'Bagnolo', 250.00, 'Umberto Sangiovanni', 'Bagnolo Gennaio .26'],
            ['2026-01-26', 'Affitto', 'Ombriano', 205.00, 'Umberto Sangiovanni', 'Ombriano Febbraio .26 - spese Confappi'],
            ['2026-02-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI FEBBRAIO  2026'],
            ['2026-02-03', 'Interessi conto', null, 3.25, 'BBVA conto deposito', 'ACCREDITO  1,75% A FRONTE DEL SALDO MENSILE'],
            ['2026-02-25', 'Affitto', 'Ombriano', 270.00, 'Umberto Sangiovanni', 'Ombriano Marzo \'26 - pratica conclusione anticipata contratto affitto'],
            ['2026-03-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI MARZO     2026'],
            ['2026-03-03', 'Interessi conto', null, 14.96, 'BBVA conto deposito', 'ACCREDITO  1,75% A FRONTE DEL SALDO MENSILE'],
            ['2026-03-24', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Aprile\'26'],
            ['2026-03-27', 'Fotovoltaico (GSE)', null, 121.35, 'GSE conv. RID438169', 'GSE CONV. RID438169 DI SANGIOVANNI GUIDO DOCUMENTI 202614609664R 202615067106R'],
            ['2026-04-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI APRILE    2026'],
            ['2026-04-02', 'Interessi conto', null, 28.67, 'BBVA conto deposito', 'ACCREDITO  1,75% A FRONTE DEL SALDO MENSILE'],
            ['2026-04-24', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Maggio .26'],
            ['2026-05-01', 'Rimborso spese immobile', 'Castione', 275.00, 'Umberto Sangiovanni', 'Castione'],
            ['2026-05-04', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI MAGGIO    2026'],
            ['2026-05-05', 'Interessi conto', null, 8.04, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ: 13.224,62 EUR AL 1,00%'],
            ['2026-05-05', 'Interessi conto', null, 73.99, 'BBVA conto deposito', 'ACCREDITO  3,00% A FRONTE DEL SALDO MENSILE'],
            ['2026-05-06', 'Interessi conto', null, -43.16, 'BBVA conto deposito', 'spese/ritenute liquidazione'],
            ['2026-05-25', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Giugno \'26'],
            ['2026-06-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI GIUGNO    2026'],
            ['2026-06-02', 'Interessi conto', null, 33.43, 'BBVA conto deposito', 'ACCREDITO  1,25% A FRONTE DEL SALDO MENSILE'],
            ['2026-06-02', 'Interessi conto', null, 9.68, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ: 15.395,68 EUR AL 1,00%'],
            ['2026-06-16', 'Cedole', null, 70.00, 'BTP Valore GN27', 'Cedola BTP Valore GN27 su nominali 4.000'],
            ['2026-06-23', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'affitto Ombriano luglio \'26'],
            ['2026-06-26', 'Rimborso spese immobile', 'Castione', 149.00, 'Umberto Sangiovanni', 'Spese condominiali Castione'],
            ['2026-06-30', 'Fotovoltaico (GSE)', null, 29.65, 'GSE conv. RID438169', 'GSE CONV. RID438169 DI SANGIOVANNI GUIDO DOCUMENTI 202616062726R'],
            ['2026-07-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI LUGLIO    2026'],
            ['2026-07-02', 'Interessi conto', null, 34.22, 'BBVA conto deposito', 'ACCREDITO  1,25% A FRONTE DEL SALDO MENSILE'],
            ['2026-07-02', 'Interessi conto', null, 10.86, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ: 17.854,05 EUR AL 1,00%'],
            ['2026-07-23', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Agosto \'26'],
            ['2026-07-28', 'Fotovoltaico (GSE)', null, 24.81, 'GSE conv. RID438169', 'GSE CONV. RID438169 DI SANGIOVANNI GUIDO DOCUMENTI 202616657817R'],
            ['2026-08-03', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI AGOSTO    2026'],
            ['2026-08-06', 'Interessi conto', null, 8.20, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ:  9.318,62 EUR AL 1,40%'],
            ['2026-08-06', 'Interessi conto', null, 54.62, 'BBVA conto deposito', 'ACCREDITO  1,60% A FRONTE DEL SALDO MENSILE'],
            ['2026-08-22', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Settembre \'26'],
            ['2026-08-28', 'Fotovoltaico (GSE)', null, 25.96, 'GSE conv. RID438169', 'GSE CONV. RID438169 DI SANGIOVANNI GUIDO DOCUMENTI 202617291381R'],
            ['2026-09-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI SETTEMBRE 2026'],
            ['2026-09-03', 'Interessi conto', null, 8.13, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ:  9.231,73 EUR AL 1,40%'],
            ['2026-09-03', 'Interessi conto', null, 54.55, 'BBVA conto deposito', 'ACCREDITO  1,60% A FRONTE DEL SALDO MENSILE'],
            ['2026-09-25', 'Affitto', 'Ombriano', 315.00, 'Umberto Sangiovanni', 'Ombriano Ottobre .26'],
            ['2026-09-28', 'Fotovoltaico (GSE)', null, 41.99, 'GSE conv. RID438169', 'GSE CONV. RID438169 DI SANGIOVANNI GUIDO DOCUMENTI 202617933335R'],
            ['2026-10-02', 'Affitto', 'Agnadello', 500.00, 'Birsan Marius', 'FITTO MESE DI OTTOBRE   2026'],
            ['2026-10-05', 'Interessi conto', null, 50.42, 'BBVA conto deposito', 'ACCREDITO  1,60% A FRONTE DEL SALDO MENSILE'],
            ['2026-10-05', 'Interessi conto', null, 5.73, 'BBVA conto deposito', 'NUOVA LIQUIDITÀ:  6.733,93 EUR AL 1,40%'],
        ];

        $inseriti = 0;
        foreach ($records as [$data, $categoria, $immobile, $importo, $causale, $note]) {
            $giaPresente = ExtraIncome::where('user_id', $userId)
                ->whereDate('data', $data)
                ->where('categoria', $categoria)
                ->where('immobile', $immobile) // null => whereNull
                ->where('importo', number_format($importo, 2, '.', ''))
                ->where('causale', $causale)
                ->exists();

            if ($giaPresente) {
                continue;
            }

            // created_at = data del movimento, così l'import non riempie gli "Ultimi movimenti" della dashboard
            $quando = Carbon::parse($data)->setTime(12, 0);
            (new ExtraIncome([
                'user_id'   => $userId,
                'categoria' => $categoria,
                'immobile'  => $immobile,
                'importo'   => $importo,
                'data'      => $data,
                'causale'   => $causale,
                'note'      => $note,
            ]))->forceFill(['created_at' => $quando, 'updated_at' => $quando])->save();
            $inseriti++;
        }

        $this->command?->info("Entrate extra: {$inseriti} inserite, " . (count($records) - $inseriti) . ' già presenti.');
    }
}
