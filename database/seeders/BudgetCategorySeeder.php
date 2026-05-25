<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BudgetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['categoria' => 'CASA', 'nome' => 'Bollette Luce Octopus + Plenitude', 'importo_annuale' => 1674.69, 'importo_mensile' => 139.56, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Bollette Luce parti comuni', 'importo_annuale' => 180.00, 'importo_mensile' => 15.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Bollette Acqua Padania Acque', 'importo_annuale' => 651.39, 'importo_mensile' => 54.28, 'periodo' => 'Quadrimestrale', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Telefono TIM', 'importo_annuale' => 802.68, 'importo_mensile' => 66.89, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Tari (Giu/Dic)', 'importo_annuale' => 68.00, 'importo_mensile' => 5.67, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Giardiniere', 'importo_annuale' => 1000.00, 'importo_mensile' => 83.33, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'IMU', 'importo_annuale' => 1940.00, 'importo_mensile' => 161.67, 'periodo' => 'Semestrale', 'note' => ''],
            ['categoria' => 'CASA', 'nome' => 'Manutenzione Fotovoltaici', 'importo_annuale' => 330.00, 'importo_mensile' => 27.50, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'SPESA', 'nome' => 'Supermercato', 'importo_annuale' => 9600.00, 'importo_mensile' => 800.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'Affitto', 'importo_annuale' => 5153.64, 'importo_mensile' => 429.47, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'Luce (ENI Plenitude)', 'importo_annuale' => 624.27, 'importo_mensile' => 52.02, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'Telefono (TIM)', 'importo_annuale' => 655.68, 'importo_mensile' => 54.64, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'Acqua', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Quadrimestrale', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'GAS (Enel)', 'importo_annuale' => 431.10, 'importo_mensile' => 35.93, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'UFFICIO', 'nome' => 'Tari', 'importo_annuale' => 50.00, 'importo_mensile' => 4.17, 'periodo' => 'Semestrale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Assicurazione Clio', 'importo_annuale' => 910.00, 'importo_mensile' => 75.83, 'periodo' => 'Annuale (Settembre)', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Assicurazione Captur', 'importo_annuale' => 719.50, 'importo_mensile' => 59.96, 'periodo' => 'Annuale (Marzo)', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Bollo Clio', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Bollo Captur', 'importo_annuale' => 75.56, 'importo_mensile' => 6.30, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Gomme Clio', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Quinquennale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Gomme Captur', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Quinquennale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Lavaggi', 'importo_annuale' => 120.00, 'importo_mensile' => 10.00, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Telepass', 'importo_annuale' => 324.70, 'importo_mensile' => 27.06, 'periodo' => 'Bimestrale', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Benzina', 'importo_annuale' => 1440.00, 'importo_mensile' => 120.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'AUTO', 'nome' => 'Risparmi acquisto auto nuove', 'importo_annuale' => 3350.00, 'importo_mensile' => 279.17, 'periodo' => 'Decennale', 'note' => ''],
            ['categoria' => 'SCUOLA GIORGIO', 'nome' => 'Asilo', 'importo_annuale' => 3500.00, 'importo_mensile' => 291.67, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'SCUOLA GIORGIO', 'nome' => 'Grest', 'importo_annuale' => 500.00, 'importo_mensile' => 41.67, 'periodo' => 'Annuale (Giugno)', 'note' => ''],
            ['categoria' => 'SCUOLA GIORGIO', 'nome' => 'Mensa', 'importo_annuale' => 1000.00, 'importo_mensile' => 83.33, 'periodo' => 'Settimanale', 'note' => ''],
            ['categoria' => 'SALUTE', 'nome' => 'Visite/Esami/Farmaci', 'importo_annuale' => 2400.00, 'importo_mensile' => 200.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'VACANZE', 'nome' => 'Viaggi vacanze e soggiorni', 'importo_annuale' => 6000.00, 'importo_mensile' => 500.00, 'periodo' => 'Semestrale', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Macbook Guido', 'importo_annuale' => 500.00, 'importo_mensile' => 41.67, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Iphone Guido', 'importo_annuale' => 325.00, 'importo_mensile' => 27.08, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Iphone Fede', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Garmin Guido', 'importo_annuale' => 80.00, 'importo_mensile' => 6.67, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Tablet/Ebook', 'importo_annuale' => 60.00, 'importo_mensile' => 5.00, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ELETTRONICA', 'nome' => 'Tastiera/Mouse/Altro', 'importo_annuale' => 150.00, 'importo_mensile' => 12.50, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY GUIDO', 'nome' => 'Scarpe da corsa', 'importo_annuale' => 750.00, 'importo_mensile' => 62.50, 'periodo' => 'Bimestrale', 'note' => ''],
            ['categoria' => 'HOBBY GUIDO', 'nome' => 'Abbigliamento da corsa', 'importo_annuale' => 100.00, 'importo_mensile' => 8.33, 'periodo' => 'Semestrale', 'note' => ''],
            ['categoria' => 'HOBBY GUIDO', 'nome' => 'Iscrizioni Gare', 'importo_annuale' => 350.00, 'importo_mensile' => 29.17, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY GUIDO', 'nome' => 'Console', 'importo_annuale' => 125.00, 'importo_mensile' => 10.42, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY GUIDO', 'nome' => 'Videogiochi', 'importo_annuale' => 200.00, 'importo_mensile' => 16.67, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Corso ricamo', 'importo_annuale' => 300.00, 'importo_mensile' => 25.00, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Make Up', 'importo_annuale' => 200.00, 'importo_mensile' => 16.67, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Materiale ricamo', 'importo_annuale' => 150.00, 'importo_mensile' => 12.50, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Corso disegno', 'importo_annuale' => 150.00, 'importo_mensile' => 12.50, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Materiale disegno', 'importo_annuale' => 200.00, 'importo_mensile' => 16.67, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'HOBBY FEDE', 'nome' => 'Palestra', 'importo_annuale' => 800.00, 'importo_mensile' => 66.67, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'ALTRO', 'nome' => 'Parrucchiere Guido', 'importo_annuale' => 220.00, 'importo_mensile' => 18.33, 'periodo' => 'Bimestrale', 'note' => ''],
            ['categoria' => 'ALTRO', 'nome' => 'Parrucchiere Fede', 'importo_annuale' => 300.00, 'importo_mensile' => 25.00, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'ALTRO', 'nome' => 'Estetista Fede', 'importo_annuale' => 300.00, 'importo_mensile' => 25.00, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'ALTRO', 'nome' => 'Parrucchiere Giorgio', 'importo_annuale' => 150.00, 'importo_mensile' => 7.50, 'periodo' => 'Bimestrale', 'note' => ''],
            ['categoria' => 'EXTRA', 'nome' => 'Regali Guido', 'importo_annuale' => 500.00, 'importo_mensile' => 41.67, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'EXTRA', 'nome' => 'Regali Fede', 'importo_annuale' => 500.00, 'importo_mensile' => 41.67, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'EXTRA', 'nome' => 'Regali altri', 'importo_annuale' => 800.00, 'importo_mensile' => 66.67, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'LIFESTYLE', 'nome' => 'Pranzi/Cene', 'importo_annuale' => 3600.00, 'importo_mensile' => 300.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'ABBIGLIAMENTO', 'nome' => 'Abbigliamento per tutti', 'importo_annuale' => 2400.00, 'importo_mensile' => 200.00, 'periodo' => 'Al bisogno', 'note' => ''],
            ['categoria' => 'ASSICURAZIONI', 'nome' => 'Vita Guido', 'importo_annuale' => 420.84, 'importo_mensile' => 35.07, 'periodo' => 'Annuale (Febbraio)', 'note' => ''],
            ['categoria' => 'ASSICURAZIONI', 'nome' => 'Casa', 'importo_annuale' => 330.00, 'importo_mensile' => 27.50, 'periodo' => 'Annuale (Gennaio)', 'note' => ''],
            ['categoria' => 'IMPREVISTI', 'nome' => 'Risparmi per imprevisti', 'importo_annuale' => 1500.00, 'importo_mensile' => 125.00, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'RISPARMI', 'nome' => 'Risparmi/Investimenti', 'importo_annuale' => 5000.00, 'importo_mensile' => 416.67, 'periodo' => 'Mensile', 'note' => ''],
            ['categoria' => 'RISPARMI', 'nome' => 'Fondo pensione', 'importo_annuale' => 5300.00, 'importo_mensile' => 441.67, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'COMMERCIALISTA', 'nome' => 'Costo commercialista', 'importo_annuale' => 1800.00, 'importo_mensile' => 150.00, 'periodo' => 'Trimestrale', 'note' => ''],
            ['categoria' => 'CASTIONE', 'nome' => 'Super condominio', 'importo_annuale' => 150.00, 'importo_mensile' => 12.50, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'CASTIONE', 'nome' => 'Condominio', 'importo_annuale' => 600.00, 'importo_mensile' => 50.00, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'CASTIONE', 'nome' => 'Bollette', 'importo_annuale' => 150.00, 'importo_mensile' => 12.50, 'periodo' => 'Annuale', 'note' => ''],
            ['categoria' => 'AGNADELLO', 'nome' => 'Sp. Condominiali Agnadello', 'importo_annuale' => 863.13, 'importo_mensile' => 71.93, 'periodo' => 'Annuale (Ottobre)', 'note' => ''],
            ['categoria' => 'AGNADELLO', 'nome' => 'Pulizia Scale Agnadello', 'importo_annuale' => 40.00, 'importo_mensile' => 3.33, 'periodo' => 'Semestrale', 'note' => ''],
            ['categoria' => 'OMBRIANO', 'nome' => 'Sp. Condominiali Ombriano', 'importo_annuale' => 756.00, 'importo_mensile' => 63.00, 'periodo' => 'Annuale', 'note' => ''],
        ];

        foreach ($categories as $i => $cat) {
            \App\Models\BudgetCategory::create(array_merge($cat, ['sort_order' => $i]));
        }
    }
}
