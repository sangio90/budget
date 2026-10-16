<?php

namespace Database\Seeders;

use App\Models\FiscalClient;
use App\Models\FiscalDeadline;
use App\Models\FiscalYear;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Fiscale\GeneratoreScadenze;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Dati iniziali della pagina Fiscale, dall'Excel analisi_movimenti_2026.xlsx (16/10/2026)
 * e dal Mod. Redditi 2026 (anno 2025):
 *  - parametri 2025–2028 (fiscal_years)
 *  - regole clienti (fattore 1,02; EAW 1,22 senza ritenuta)
 *  - scadenziario 2025–2027 (fogli "Scadenze IVA" e "Scadenze INPS-IRPEF")
 * Idempotente: crea solo ciò che manca (anno, causale cliente o chiave scadenza), non sovrascrive le modifiche.
 *
 *   php artisan db:seed --class=FiscaleSeeder
 */
class FiscaleSeeder extends Seeder
{
    private const USER_ID = 1;

    public function run(): void
    {
        $userId = User::whereKey(self::USER_ID)->value('id') ?? User::first()?->id;
        if (!$userId) {
            $this->command?->warn('Nessun utente trovato: dati fiscali non importati.');
            return;
        }

        $anni     = $this->anni($userId);
        $clienti  = $this->clienti($userId);
        $scadenze = $this->scadenze($userId);

        $this->command?->info("Fiscale: {$anni} anni, {$clienti} clienti, {$scadenze} scadenze inseriti (il resto era già presente).");
    }

    private function anni(int $userId): int
    {
        $comuni = [
            'aliquota_inps' => 0.2607, 'massimale_inps' => 122000, 'aliquota_iva' => 0.22, 'aliquota_ritenuta' => 0.20,
            'scaglioni_add_regionale' => FiscalYear::SCAGLIONI_ADD_REGIONALE, 'aliquota_add_comunale' => 0.0074,
            'redditi_fabbricati' => 12472, 'cedolare_secca' => 2619, 'fondo_pensione' => 2500,
        ];

        $anni = [
            2025 => [
                // Scaglioni IRPEF 2025: 23 / 35 / 43%
                'scaglioni_irpef' => [
                    ['fino' => 28000, 'aliquota' => 0.23], ['fino' => 50000, 'aliquota' => 0.35], ['fino' => null, 'aliquota' => 0.43],
                ],
                'detrazioni' => 17170, 'accantonamento_perc' => 0.30,
                'note' => 'Mod. Redditi 2026: reddito professionale 99.050, imponibile 71.573, detrazioni 17.170, credito IRPEF 28.560, '
                    . 'INPS dovuto 25.822, cedolare 2.619, add. regionale 1.142, comunale 365.',
            ],
            2026 => [
                'scaglioni_irpef' => FiscalYear::SCAGLIONI_IRPEF,
                'detrazioni' => 17170,
                'prec_inps_dovuto' => 25822, 'prec_inps_acconti' => 23058, 'prec_cedolare' => 2619, 'iva_base_acconto' => 9304.95,
                // Mod. Redditi 2026 (anno 2025): RN26 imposta netta, RN33 ritenute
                'prec_irpef_netta' => 6246, 'prec_ritenute' => 34806,
                'credito_irpef_iniziale' => 28560,
                // Credito IVA 2025 da integrativa (1.795): 1.025 usati nel Q1 2026, 770 ancora disponibili
                'credito_iva_iniziale' => 1795,
                'accantonamento_perc' => 0.30,
                'note' => 'Detrazioni: 110% e risparmio energetico 2023 (ultima rata 2026), ristrutturazione/energetico/mobili 2018 (ultima rata 2027), spese sanitarie.',
            ],
            2027 => [
                'scaglioni_irpef' => FiscalYear::SCAGLIONI_IRPEF,
                'detrazioni' => 6301, 'accantonamento_perc' => 0.30,
                'note' => 'Finite le detrazioni 2023: restano ristrutturazione 50%, energetico 65%, mobili 2018 e spese sanitarie.',
            ],
            2028 => [
                'scaglioni_irpef' => FiscalYear::SCAGLIONI_IRPEF,
                'detrazioni' => 95, 'accantonamento_perc' => 0.33,
                'note' => 'Restano solo le spese sanitarie ricorrenti.',
            ],
        ];

        $creati = 0;
        foreach ($anni as $anno => $valori) {
            $r = FiscalYear::firstOrCreate(['user_id' => $userId, 'anno' => $anno], $valori + $comuni);
            $creati += $r->wasRecentlyCreated ? 1 : 0;
        }

        return $creati;
    }

    private function clienti(int $userId): int
    {
        $clienti = [
            'ID3' => null, 'Italpacking' => null, 'ILT' => null,
            'BRV' => null, 'Diego' => null, 'Dibotek' => null, 'Vetraco' => null, 'Mercurio Sistemi' => null,
            'K3' => null, 'Lacchinelli' => null, 'Prada' => null,
        ];

        $creati = 0;
        foreach ($clienti as $causale => $nota) {
            $r = FiscalClient::firstOrCreate(['user_id' => $userId, 'causale' => $causale],
                ['aliquota_iva' => 0.22, 'aliquota_ritenuta' => 0.20, 'note' => $nota]);
            $creati += $r->wasRecentlyCreated ? 1 : 0;
        }
        $a = FiscalClient::firstOrCreate(['user_id' => $userId, 'causale' => 'Apir'],
            ['aliquota_iva' => 0, 'aliquota_ritenuta' => 0, 'note' => 'Cliente senza IVA né ritenuta (fattore 1,00)']);
        $creati += $a->wasRecentlyCreated ? 1 : 0;
        $r = FiscalClient::firstOrCreate(['user_id' => $userId, 'causale' => 'EAW'],
            ['aliquota_iva' => 0.22, 'aliquota_ritenuta' => 0, 'note' => 'Europe at Work: senza ritenuta (fattore 1,22)']);

        return $creati + ($r->wasRecentlyCreated ? 1 : 0);
    }

    private function scadenze(int $userId): int
    {
        $comp  = 'Presumibilmente compensato con il credito IRPEF (nessun F24 su ING/BBVA)';
        $o     = GeneratoreScadenze::ORDINE;

        // [chiave, scadenza, tributo, descrizione, competenza, importo, compensato, pagato, data pagamento, stato, nota]
        $righe = [
            // --- IVA 2025 (LIPE) -----------------------------------------------------------
            ['iva:q1:2025', '2025-05-16', 'IVA', 'IVA 1° trimestre 2025', 2025, 9942.36, 0, 9942.36, '2025-05-16', 'pagato', 'IVA 9.843,92 + interessi 98,44'],
            ['iva:q2:2025', '2025-08-20', 'IVA', 'IVA 2° trimestre 2025', 2025, 8552.30, 0, 8552.30, '2025-08-20', 'pagato', 'IVA 8.467,62 + interessi 84,68'],
            ['iva:q3:2025', '2025-11-17', 'IVA', 'IVA 3° trimestre 2025', 2025, 6240.31, 0, 6240.31, '2025-11-17', 'pagato', 'IVA 6.178,52 + interessi 61,79'],
            ['iva:acconto:2025', '2025-12-29', 'IVA', 'Acconto IVA 2025', 2025, 10330.14, 0, 10330.14, '2025-12-29', 'pagato', 'Metodo storico'],
            ['iva:saldo:2025', '2026-03-16', 'IVA', '4° trimestre / saldo annuale 2025', 2025, 0, 0, 0, null, 'pagato',
                'Nulla da versare: IVA Q4 9.304,95 < acconto 10.330,14 → credito 1.025 (usato nel Q1 2026) + 770 da integrativa = 1.795'],
            // --- IVA 2026 ------------------------------------------------------------------
            ['iva:q1:2026', '2026-05-18', 'IVA', 'IVA 1° trimestre 2026', 2026, 10672.13, 1025, 9647.13, '2026-05-18', 'pagato',
                'IVA 10.576,61 + interessi 95,52 − credito 2025 1.025; pagato da BBVA'],
            ['iva:q2:2026', '2026-08-20', 'IVA', 'IVA 2° trimestre 2026', 2026, 8251.09, 0, 8251.09, '2026-08-12', 'pagato',
                'IVA 8.169,40 + interessi 81,69; pagato da ING'],
            ['iva:q3:2026', '2026-11-16', 'IVA', 'IVA 3° trimestre 2026', 2026, 8837.50, null, null, null, 'stimato',
                'Stima Excel ~72% IVA incassi Q3 (8.750 + 1%), al netto del credito IVA residuo 770'],
            ['iva:acconto:2026', '2026-12-28', 'IVA', 'Acconto IVA 2026', 2026, 8188.36, null, null, null, 'da_pagare',
                'Metodo storico: 88% × 9.304,95 (27/12 è domenica)'],
            ['iva:saldo:2026', '2027-03-16', 'IVA', '4° trimestre / saldo annuale 2026', 2026, 311.64, null, null, null, 'stimato',
                'Stima Q4 2026 8.500 − acconto 8.188,36'],
            // --- INPS / IRPEF / addizionali / cedolare: 30/06/2026 ---------------------------
            ['inps:saldo:2025', '2026-06-30', 'INPS', 'INPS gestione separata – saldo 2025', 2025, 2764, 2764, null, '2026-06-30', 'compensato', $comp . ' – RR7'],
            ['inps:acconto1:2026', '2026-06-30', 'INPS', 'INPS – 1° acconto 2026', 2026, 10328.80, 10328.80, null, '2026-06-30', 'compensato', $comp . ' – 40% di 25.822'],
            ['irpef:saldo:2025', '2026-06-30', 'IRPEF', 'IRPEF – saldo 2025', 2025, 0, 0, null, null, 'compensato', 'A credito 28.560 (RX1)'],
            ['irpef:acconto1:2026', '2026-06-30', 'IRPEF', 'IRPEF – 1° acconto 2026', 2026, 0, 0, null, null, 'compensato', 'Non dovuto (RN62 = 0)'],
            ['addreg:saldo:2025', '2026-06-30', 'Addizionale regionale', 'Addizionale regionale – saldo 2025', 2025, 1142, 1142, null, '2026-06-30', 'compensato', $comp . ' – RX2'],
            ['addcom:saldo:2025', '2026-06-30', 'Addizionale comunale', 'Addizionale comunale – saldo 2025', 2025, 365, 365, null, '2026-06-30', 'compensato', $comp . ' – RX3'],
            ['addcom:acconto:2026', '2026-06-30', 'Addizionale comunale', 'Addizionale comunale – acconto 2026', 2026, 158, 158, null, '2026-06-30', 'compensato', $comp . ' – RV17'],
            ['ced:saldo:2025', '2026-06-30', 'Cedolare secca', 'Cedolare secca – saldo 2025', 2025, 107, 107, null, '2026-06-30', 'compensato', $comp . ' – RX4'],
            ['ced:acconto1:2026', '2026-06-30', 'Cedolare secca', 'Cedolare secca – 1° acconto 2026', 2026, 1310, 1310, null, '2026-06-30', 'compensato', $comp . ' – LC2 col. 1'],
            // --- 30/11/2026 ----------------------------------------------------------------
            ['inps:acconto2:2026', '2026-11-30', 'INPS', 'INPS – 2° acconto 2026', 2026, 10328.80, null, null, null, 'da_pagare', 'In scadenza – coperto dal credito IRPEF'],
            ['ced:acconto2:2026', '2026-11-30', 'Cedolare secca', 'Cedolare secca – 2° acconto 2026', 2026, 1310, null, null, null, 'da_pagare', 'In scadenza – coperto dal credito IRPEF'],
            ['irpef:acconto2:2026', '2026-11-30', 'IRPEF', 'IRPEF – 2° acconto 2026', 2026, 0, 0, null, null, 'compensato', 'Non dovuto (RN62 = 0)'],
            // --- 2027 (stime Excel) --------------------------------------------------------
            ['irpef:saldo:2026', '2027-06-30', 'IRPEF', 'IRPEF – saldo 2026 (a credito)', 2026, -5194, null, null, null, 'stimato', 'Ritenute ~45,5k − IRPEF netta ~40,3k: credito che si aggiunge al residuo'],
            ['inps:saldo:2026', '2027-06-30', 'INPS', 'INPS gestione separata – saldo 2026', 2026, 11147.80, null, null, null, 'stimato', '26,07% × massimale 122.000 − acconti 20.657,60'],
            ['inps:acconto1:2027', '2027-06-30', 'INPS', 'INPS – 1° acconto 2027', 2027, 12722.16, null, null, null, 'stimato', '40% × 31.805'],
            ['addreg:saldo:2026', '2027-06-30', 'Addizionale regionale', 'Addizionale regionale – saldo 2026', 2026, 2530, null, null, null, 'stimato', 'Lombardia ~1,73% marginale su ~152k'],
            ['addcom:saldo:2026', '2027-06-30', 'Addizionale comunale', 'Addizionale comunale – saldo 2026', 2026, 965, null, null, null, 'stimato', '~0,74% su ~152k − acconto 158'],
            ['addcom:acconto:2027', '2027-06-30', 'Addizionale comunale', 'Addizionale comunale – acconto 2027', 2027, 335, null, null, null, 'stimato', '30% × addizionale 2026'],
            ['ced:acconto1:2027', '2027-06-30', 'Cedolare secca', 'Cedolare secca – 1° acconto 2027', 2027, 1310, null, null, null, 'stimato', 'Come 2026'],
            ['inps:acconto2:2027', '2027-11-30', 'INPS', 'INPS – 2° acconto 2027', 2027, 12722.16, null, null, null, 'stimato', '40% × 31.805'],
            ['ced:acconto2:2027', '2027-11-30', 'Cedolare secca', 'Cedolare secca – 2° acconto 2027', 2027, 1310, null, null, null, 'stimato', 'Come 2026'],
            // --- IMU (non compensabile con il credito IRPEF) ---------------------------------
            ['imu:acconto:2026', '2026-06-16', 'IMU', 'IMU – Acconto 2026', 2026, 700, 0, 700, '2026-06-16', 'pagato', 'F24 del 16/06/2026 da ING'],
            ['imu:saldo:2026', '2026-12-16', 'IMU', 'IMU – Saldo 2026', 2026, 700, null, null, null, 'stimato',
                'Stima uguale all\'acconto (nel budget fambudget l\'IMU annua è 1.940: verificare l\'importo)'],
            ['imu:acconto:2027', '2027-06-16', 'IMU', 'IMU – Acconto 2027', 2027, 700, null, null, null, 'stimato', 'Come 2026'],
            ['imu:saldo:2027', '2027-12-16', 'IMU', 'IMU – Saldo 2027', 2027, 700, null, null, null, 'stimato', 'Come 2026'],
        ];

        $creati = 0;
        foreach ($righe as [$chiave, $scad, $tributo, $descr, $competenza, $importo, $compensato, $pagato, $dataPag, $stato, $nota]) {
            if (FiscalDeadline::where('user_id', $userId)->where('chiave', $chiave)->exists()) {
                continue;
            }

            [$t, $tipo] = explode(':', $chiave);
            FiscalDeadline::create([
                'user_id'          => $userId,
                'chiave'           => $chiave,
                'scadenza'         => $scad,
                'tributo'          => $tributo,
                'descrizione'      => $descr,
                'anno_competenza'  => $competenza,
                'importo_previsto' => $importo,
                'compensato'       => $compensato,
                'importo_pagato'   => $pagato,
                'data_pagamento'   => $dataPag,
                'stato'            => $stato,
                'transaction_id'   => $pagato ? $this->f24($userId, $pagato, $dataPag ?? $scad) : null,
                // Le righe stimate restano aggiornabili da "Genera stime"; le altre sono dati effettivi
                'manuale'          => false,
                'ordine'           => $o[$t === 'iva' ? 'iva' : "$t:$tipo"] ?? 90,
                'note'             => $nota,
            ]);
            $creati++;
        }

        return $creati;
    }

    /** F24 registrato nelle transactions con lo stesso importo entro 45 giorni (se c'è). */
    private function f24(int $userId, float $importo, string $data): ?int
    {
        $d = Carbon::parse($data);

        return Transaction::where('user_id', $userId)->where('tipo', 'f24')
            ->whereBetween('importo', [$importo - 0.005, $importo + 0.005])
            ->whereDate('data', '>=', $d->copy()->subDays(45)->toDateString())
            ->whereDate('data', '<=', $d->copy()->addDays(45)->toDateString())
            ->get(['id', 'data'])
            ->sortBy(fn($t) => abs($t->data->diffInDays($d)))   // la più vicina alla scadenza
            ->first()?->id;
    }
}
