<?php

namespace Tests\Feature;

use App\Models\BudgetCategory;
use App\Models\BudgetExpense;
use App\Models\FiscalClient;
use App\Models\FiscalDeadline;
use App\Models\FiscalYear;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Fiscale\CalcoloImposte;
use App\Services\Fiscale\GeneratoreScadenze;
use App\Services\Fiscale\ProiezioneFiscale;
use App\Services\Fiscale\Scadenziario;
use Database\Seeders\FiscaleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscaleTest extends TestCase
{
    use RefreshDatabase;

    /** Ipotesi 2026 del foglio "Proiezione 2026-2028" (Excel del 16/10/2026). */
    private const IPOTESI_EXCEL = [
        'compensi_previsti' => 238735, 'costi_previsti' => 73450, 'compensi_ra_previsti' => 227565,
        'affitti_previsti' => 9924, 'iva_annua_prevista' => 35000, 'spese_famiglia_previste' => 62634,
    ];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-16 10:00:00');
        $this->user = User::factory()->create();   // id 1, usato dal seeder
    }

    private function seedExcel(bool $ipotesi = true): void
    {
        $this->seed(FiscaleSeeder::class);
        if ($ipotesi) {
            FiscalYear::where('anno', 2026)->update(self::IPOTESI_EXCEL);
        }
    }

    // ------------------------------------------------------------------ calcoli

    public function test_calcolo_2026_coincide_con_excel(): void
    {
        $p = new FiscalYear(['anno' => 2026, 'scaglioni_irpef' => FiscalYear::SCAGLIONI_IRPEF,
            'redditi_fabbricati' => 12472, 'cedolare_secca' => 2619, 'fondo_pensione' => 2500, 'detrazioni' => 17170]);

        $r = (new CalcoloImposte)->calcola([
            'compensi' => 238735, 'costi' => 73450, 'compensi_ra' => 227565, 'affitti' => 9924,
            'inps_deducibile' => 23421.6, 'iva_annua' => 35000, 'spese_famiglia' => 62634,
        ], $p);

        $this->assertEquals(165285, $r['reddito']);
        $this->assertEquals(31805, $r['inps']);
        $this->assertEqualsWithDelta(151835.4, $r['imponibile_irpef'], 0.01);
        $this->assertEquals(57489, $r['irpef_lorda']);
        $this->assertEquals(40319, $r['irpef_netta']);
        $this->assertEquals(45513, $r['ritenute']);
        $this->assertEquals(-5194, $r['irpef_saldo']);
        $this->assertEquals(2530, $r['add_regionale']);
        $this->assertEquals(1124, $r['add_comunale']);
        $this->assertEquals(78397, $r['totale_imposte']);
        $this->assertEquals(96812, $r['netto']);
        $this->assertEquals(8068, $r['netto_mensile']);
        $this->assertEquals(2848, $r['risparmio_mensile']);
        $this->assertEquals(6090, $r['accantonamento_mensile']);
    }

    public function test_scaglioni(): void
    {
        $this->assertEqualsWithDelta(0.23 * 20000, CalcoloImposte::aScaglioni(20000, FiscalYear::SCAGLIONI_IRPEF), 0.001);
        $this->assertEqualsWithDelta(6440 + 7260 + 0.43 * 10000, CalcoloImposte::aScaglioni(60000, FiscalYear::SCAGLIONI_IRPEF), 0.001);
        $this->assertEquals(0, CalcoloImposte::aScaglioni(0, FiscalYear::SCAGLIONI_IRPEF));
    }

    public function test_dettaglio_irpef_per_scaglione(): void
    {
        $scaglioni = FiscalYear::SCAGLIONI_IRPEF;
        $righe = CalcoloImposte::dettaglioScaglioni(151835.4, $scaglioni);

        $this->assertCount(3, $righe);
        $this->assertEqualsWithDelta(28000, $righe[0]['base'], 0.01);
        $this->assertEqualsWithDelta(6440, $righe[0]['imposta'], 0.01);
        $this->assertEqualsWithDelta(22000, $righe[1]['base'], 0.01);
        $this->assertEqualsWithDelta(7260, $righe[1]['imposta'], 0.01);
        $this->assertNull($righe[2]['fino']);
        $this->assertEqualsWithDelta(101835.4, $righe[2]['base'], 0.01);
        $this->assertEqualsWithDelta(
            CalcoloImposte::aScaglioni(151835.4, $scaglioni),
            array_sum(array_column($righe, 'imposta')),
            0.001
        );

        // reddito sotto il primo scaglione: le fasce successive restano a zero
        $basso = CalcoloImposte::dettaglioScaglioni(20000, $scaglioni);
        $this->assertEqualsWithDelta(20000, $basso[0]['base'], 0.01);
        $this->assertEquals(0, $basso[1]['base']);
        $this->assertEquals(0, $basso[2]['base']);
    }

    public function test_proiezione_triennio_coincide_con_excel(): void
    {
        $this->seedExcel();

        $t = app(ProiezioneFiscale::class)->triennio($this->user->id, 2026);

        // INPS deducibile per cassa: saldo anno prima + acconti 80%
        $this->assertEqualsWithDelta(23421.6, $t[2026]['inps_deducibile'], 0.01);
        $this->assertEqualsWithDelta(36591.4, $t[2027]['inps_deducibile'], 0.01);
        $this->assertEqualsWithDelta(31805, $t[2028]['inps_deducibile'], 0.01);

        $this->assertEquals(40319, $t[2026]['irpef_netta']);
        $this->assertEquals(45525, $t[2027]['irpef_netta']);
        $this->assertEquals(53789, $t[2028]['irpef_netta']);
        $this->assertEquals(31805, $t[2026]['inps']);

        $this->assertEquals(8068, $t[2026]['netto_mensile']);
        $this->assertEquals(7661, $t[2027]['netto_mensile']);
        $this->assertEquals(6962, $t[2028]['netto_mensile']);
        // come l'Excel (6.090 / 6.064 / 6.762) + IMU 1.400 l'anno (≈117 al mese; il 2028 eredita quella del 2027)
        foreach ([2026 => 6090, 2027 => 6064, 2028 => 6762] as $a => $excel) {
            $this->assertEqualsWithDelta($excel + 1400 / 12, $t[$a]['accantonamento_mensile'], 1.01);
            $this->assertEqualsWithDelta(1400, $t[$a]['imu'], 0.01);
        }
    }

    public function test_consuntivo_e_annualizzazione_dai_movimenti(): void
    {
        FiscalYear::create(['user_id' => $this->user->id, 'anno' => 2026]);
        FiscalClient::create(['user_id' => $this->user->id, 'causale' => 'EAW', 'aliquota_iva' => 0.22, 'aliquota_ritenuta' => 0]);
        $ufficio = BudgetCategory::create(['categoria' => 'UFFICIO', 'nome' => 'Affitto', 'aziendale' => true, 'importo_annuale' => 0, 'importo_mensile' => 0]);
        $casa    = BudgetCategory::create(['categoria' => 'CASA', 'nome' => 'Luce', 'importo_annuale' => 0, 'importo_mensile' => 0]);

        $tx = fn($tipo, $importo, $data, $causale) => Transaction::create(['user_id' => $this->user->id, 'tipo' => $tipo, 'importo' => $importo, 'data' => $data, 'causale' => $causale]);
        $tx('entrata', 10200, '2026-02-10', 'ID3');      // default 1,02 → 10.000
        $tx('entrata', 1220, '2026-08-10', 'EAW');       // 1,22 senza ritenuta → 1.000
        $tx('entrata', 5100, '2026-11-10', 'ID3');       // dopo "oggi": escluso
        $tx('uscita', 3000, '2026-03-31', 'Emanuele');
        $tx('f24', 500, '2026-05-18', 'iva');
        BudgetExpense::create(['user_id' => $this->user->id, 'budget_category_id' => $ufficio->id, 'importo' => 400, 'data' => '2026-04-01']);
        BudgetExpense::create(['user_id' => $this->user->id, 'budget_category_id' => $casa->id, 'importo' => 150, 'data' => '2026-04-01']);

        $proiezione = app(ProiezioneFiscale::class);
        $ip = $proiezione->ipotesi($this->user->id, 2026);
        $c  = $ip['consuntivo'];

        $this->assertEqualsWithDelta(11420, $c['incassato'], 0.01);
        $this->assertEqualsWithDelta(11000, $c['imponibile'], 0.01);
        $this->assertEqualsWithDelta(2420, $c['iva_incassata'], 0.01);
        $this->assertEqualsWithDelta(2000, $c['ritenute'], 0.01);
        $this->assertEqualsWithDelta(10000, $c['compensi_ra'], 0.01);
        $this->assertEqualsWithDelta(3400, $c['costi'], 0.01);
        $this->assertEqualsWithDelta(150, $c['spese_famiglia'], 0.01);
        $this->assertEqualsWithDelta(500, $c['f24'], 0.01);
        $this->assertEqualsWithDelta(2200, $c['iva_trimestri'][1], 0.01);
        $this->assertEqualsWithDelta(220, $c['iva_trimestri'][3], 0.01);

        // Al 16/10 sono passati ~9,5 mesi: valore a oggi + media degli ultimi 3 mesi (16/07–16/10) × 2,5 mesi che mancano
        $this->assertEqualsWithDelta(9.5, $ip['mesi'], 0.01);
        $mesiRecenti = $ip['mesi'] - round(\Carbon\Carbon::parse('2026-07-15')->dayOfYear / 365 * 12, 2);
        $this->assertEquals(round(11000 + 1000 / $mesiRecenti * (12 - $ip['mesi'])), $ip['valori']['compensi']);
        // nessun costo negli ultimi 3 mesi: i costi restano quelli a oggi
        $this->assertEquals(3400, $ip['valori']['costi']);
        $this->assertSame('a oggi + ritmo ultimi 3 mesi', $ip['fonte']['compensi']);

        // L'anno dopo eredita le ipotesi dell'anno in corso
        $this->assertEquals($ip['valori']['compensi'], $proiezione->ipotesi($this->user->id, 2027)['valori']['compensi']);
    }

    // ------------------------------------------------------------- scadenziario

    public function test_scadenziario_scala_il_credito_come_excel(): void
    {
        $this->seedExcel();
        $righe = app(Scadenziario::class)->righe($this->user->id)->keyBy('chiave');

        $this->assertEqualsWithDelta(12385.2, $righe['ced:acconto1:2026']->residuo_irpef, 0.01);
        $this->assertEqualsWithDelta(10328.8, $righe['inps:acconto2:2026']->compensato_calc, 0.01);
        $this->assertEqualsWithDelta(746.40, $righe['ced:acconto2:2026']->residuo_irpef, 0.01);
        $this->assertEqualsWithDelta(0, $righe['ced:acconto2:2026']->da_versare_calc, 0.01);

        // IVA: Q3 al netto del credito residuo 770, acconto storico 88% × 9.304,95
        $this->assertEqualsWithDelta(8067.5, $righe['iva:q3:2026']->da_versare_calc, 0.01);
        $this->assertEqualsWithDelta(8188.36, (float) $righe['iva:acconto:2026']->importo_previsto, 0.001);

        // Giugno 2027: il credito IRPEF 2026 (5.194) si aggiunge al residuo e copre parte del saldo INPS
        $this->assertEqualsWithDelta(5940.4, $righe['inps:saldo:2026']->compensato_calc, 0.01);
        $tot = app(Scadenziario::class)->totaliPerData(app(Scadenziario::class)->righe($this->user->id));
        $this->assertEqualsWithDelta(23069.56, $tot['2027-06-30']['da_versare'], 0.01);
    }

    public function test_fondo_tasse(): void
    {
        $this->seedExcel();
        $f = app(Scadenziario::class)->fondoTasse($this->user->id, now());

        $this->assertSame('2026-11-16', $f['prossima_grossa']->toDateString());
        $this->assertEqualsWithDelta(8067.5, $f['fino_prossima_grossa'], 0.01);
        // 16/11 IVA Q3 + 30/11 (coperto da credito) + saldo IMU 16/12 + acconto IVA + saldo IVA + acconto IMU 16/06 + 30/06/2027
        $this->assertEqualsWithDelta(8067.5 + 700 + 8188.36 + 311.64 + 700 + 23069.56, $f['fino_giugno'], 0.01);
    }

    public function test_imu_nello_scadenziario_e_nelle_stime(): void
    {
        $this->seedExcel();
        $righe = app(Scadenziario::class)->righe($this->user->id)->keyBy('chiave');

        // pagata in contanti con F24: non usa il credito IRPEF
        $this->assertEqualsWithDelta(0, $righe['imu:acconto:2026']->compensato_calc, 0.01);
        $this->assertEqualsWithDelta(700, $righe['imu:saldo:2026']->da_versare_calc, 0.01);

        app(GeneratoreScadenze::class)->genera($this->user->id, 2028);
        $imu = FiscalDeadline::where('tributo', 'IMU')->where('anno_competenza', 2028)->get()->keyBy('chiave');
        $this->assertEqualsWithDelta(700, (float) $imu['imu:acconto:2028']->importo_previsto, 0.01);
        $this->assertSame('2028-06-16', $imu['imu:acconto:2028']->scadenza->toDateString());
        $this->assertSame('2028-12-18', $imu['imu:saldo:2028']->scadenza->toDateString()); // 16/12/2028 è sabato
    }

    public function test_genera_stime_non_tocca_righe_pagate_o_manuali(): void
    {
        $this->seedExcel();
        FiscalDeadline::where('chiave', 'addcom:acconto:2027')->update(['manuale' => true, 'importo_previsto' => 999]);

        $esito = app(GeneratoreScadenze::class)->genera($this->user->id, 2027);
        $righe = FiscalDeadline::all()->keyBy('chiave');

        $this->assertGreaterThan(0, $esito['creati']);
        $this->assertGreaterThan(0, $esito['protetti']);
        // Nuove righe IVA 2027 e acconti calcolati sul dovuto 2026
        $this->assertTrue($righe->has('iva:q1:2027'));
        $this->assertSame('2027-12-27', $righe['iva:acconto:2027']->scadenza->toDateString());
        $this->assertEqualsWithDelta(0.4 * 31805, (float) $righe['inps:acconto1:2027']->importo_previsto, 0.01);
        $this->assertEqualsWithDelta(31805 - 20657.6, (float) $righe['inps:saldo:2026']->importo_previsto, 0.01);
        $this->assertEqualsWithDelta(40319 - 45513, (float) $righe['irpef:saldo:2026']->importo_previsto, 0.01);
        $this->assertEqualsWithDelta(1310, (float) $righe['ced:acconto1:2027']->importo_previsto, 0.01);
        // Riga modificata a mano e riga "da pagare" invariate
        $this->assertEqualsWithDelta(999, (float) $righe['addcom:acconto:2027']->importo_previsto, 0.001);
        $this->assertEqualsWithDelta(8188.36, (float) $righe['iva:acconto:2026']->importo_previsto, 0.001);

        // Rilanciato non duplica
        $n = FiscalDeadline::count();
        app(GeneratoreScadenze::class)->genera($this->user->id, 2027);
        $this->assertSame($n, FiscalDeadline::count());
    }

    public function test_giorno_lavorativo(): void
    {
        $this->assertSame('2026-12-28', GeneratoreScadenze::giornoLavorativo(\Carbon\Carbon::parse('2026-12-27'))->toDateString());
        $this->assertSame('2026-05-18', GeneratoreScadenze::giornoLavorativo(\Carbon\Carbon::parse('2026-05-16'))->toDateString());
        $this->assertSame('2026-11-16', GeneratoreScadenze::giornoLavorativo(\Carbon\Carbon::parse('2026-11-16'))->toDateString());
    }

    public function test_seeder_idempotente(): void
    {
        $this->seed(FiscaleSeeder::class);
        $conteggi = [FiscalYear::count(), FiscalClient::count(), FiscalDeadline::count()];
        $this->seed(FiscaleSeeder::class);

        $this->assertSame([4, 13], [$conteggi[0], $conteggi[1]]);
        $this->assertSame($conteggi, [FiscalYear::count(), FiscalClient::count(), FiscalDeadline::count()]);
        $this->assertEqualsWithDelta(1.22, FiscalClient::where('causale', 'EAW')->first()->fattore(), 0.0001);
    }

    // -------------------------------------------------------------------- pagina

    public function test_guest_redirect(): void
    {
        $this->get('/fiscale')->assertRedirect('/login');
    }

    public function test_pagina_fiscale(): void
    {
        $this->seedExcel();

        $this->actingAs($this->user)->get('/fiscale')
            ->assertOk()
            ->assertSee('Fiscale 2026')
            ->assertSee('8.068 €', false)
            ->assertSee('Quanto dovresti avere da parte')
            ->assertSee('Acconto IVA 2026')
            ->assertSee('8.188,36 €', false)
            ->assertDontSee('Fondo tasse');

        foreach ([2025, 2027, 2028, 2030] as $anno) {
            $this->actingAs($this->user)->get('/fiscale?anno=' . $anno)->assertOk()->assertSee("Fiscale $anno");
        }
    }

    public function test_pagina_senza_dati(): void
    {
        $this->actingAs($this->user)->get('/fiscale')->assertOk()->assertSee('Nessuna scadenza nel 2026');
    }

    public function test_parametri_salvati_con_percentuali_e_scaglioni(): void
    {
        $this->actingAs($this->user)->put('/fiscale/parametri/2029', [
            'aliquota_inps' => '26.07', 'massimale_inps' => '125000', 'aliquota_iva' => '22', 'aliquota_ritenuta' => '20',
            'aliquota_add_comunale' => '0.8', 'accantonamento_perc' => '33', 'detrazioni' => '95',
            'scaglioni_irpef' => '28000:23; 50000:33; *:43', 'scaglioni_add_regionale' => '',
            'compensi_previsti' => '',
        ])->assertSessionHasNoErrors()->assertRedirect(route('fiscale.index', ['anno' => 2029]));

        $p = FiscalYear::where('anno', 2029)->sole();
        $this->assertEqualsWithDelta(0.2607, (float) $p->aliquota_inps, 0.000001);
        $this->assertEqualsWithDelta(0.008, (float) $p->aliquota_add_comunale, 0.000001);
        $this->assertEqualsWithDelta(0.33, (float) $p->accantonamento_perc, 0.000001);
        $this->assertEquals(125000, (float) $p->massimale_inps);
        $this->assertEquals([['fino' => 28000, 'aliquota' => 0.23], ['fino' => 50000, 'aliquota' => 0.33], ['fino' => null, 'aliquota' => 0.43]], $p->scaglioni_irpef);
        $this->assertNull($p->scaglioni_add_regionale);
        $this->assertNull($p->compensi_previsti);

        $this->actingAs($this->user)->put('/fiscale/parametri/2029', ['scaglioni_irpef' => 'abc'])
            ->assertSessionHasErrors('scaglioni_irpef');
    }

    public function test_crud_scadenze(): void
    {
        $dati = [
            'scadenza' => '2026-12-16', 'tributo' => 'IMU', 'descrizione' => 'IMU saldo 2026', 'anno_competenza' => 2026,
            'importo_previsto' => '970', 'stato' => 'da_pagare',
        ];
        $this->actingAs($this->user)->post('/fiscale/scadenze', $dati)->assertSessionHasNoErrors();
        $s = FiscalDeadline::sole();
        $this->assertTrue($s->manuale);
        $this->assertEqualsWithDelta(970, (float) $s->da_versare, 0.001);

        $f24 = Transaction::create(['user_id' => $this->user->id, 'tipo' => 'f24', 'importo' => 970, 'data' => '2026-12-15', 'causale' => 'IMU']);
        $this->actingAs($this->user)->get(route('fiscale.scadenze.edit', $s))->assertOk()->assertSee('IMU saldo 2026');
        $this->actingAs($this->user)->put(route('fiscale.scadenze.update', $s), array_merge($dati, [
            'stato' => 'pagato', 'importo_pagato' => '970', 'data_pagamento' => '2026-12-15', 'transaction_id' => $f24->id,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('fiscale.index', ['anno' => 2026]));
        $s->refresh();
        $this->assertSame('pagato', $s->stato);
        $this->assertSame($f24->id, $s->transaction_id);

        $this->actingAs($this->user)->post('/fiscale/scadenze', ['tributo' => 'XYZ'])
            ->assertSessionHasErrors(['scadenza', 'tributo', 'descrizione', 'importo_previsto', 'stato']);

        $this->actingAs($this->user)->delete(route('fiscale.scadenze.destroy', $s))->assertRedirect();
        $this->assertModelMissing($s);
    }

    public function test_modifica_a_mano_protegge_dalla_rigenerazione(): void
    {
        $this->seedExcel();
        $s = FiscalDeadline::where('chiave', 'inps:acconto1:2027')->sole();

        $this->actingAs($this->user)->put(route('fiscale.scadenze.update', $s), [
            'scadenza' => '2027-06-30', 'tributo' => 'INPS', 'descrizione' => $s->descrizione, 'anno_competenza' => 2027,
            'importo_previsto' => '12000', 'stato' => 'stimato',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($s->fresh()->manuale);

        app(GeneratoreScadenze::class)->genera($this->user->id, 2027);
        $this->assertEqualsWithDelta(12000, (float) $s->fresh()->importo_previsto, 0.001);
    }

    public function test_scadenze_di_altri_utenti_vietate(): void
    {
        $altro = User::factory()->create();
        $s = FiscalDeadline::create(['user_id' => $altro->id, 'scadenza' => '2026-11-16', 'tributo' => 'IVA', 'descrizione' => 'x',
            'anno_competenza' => 2026, 'importo_previsto' => 1, 'stato' => 'da_pagare']);

        $this->actingAs($this->user)->get(route('fiscale.scadenze.edit', $s))->assertForbidden();
        $this->actingAs($this->user)->delete(route('fiscale.scadenze.destroy', $s))->assertForbidden();
        $this->assertModelExists($s);
    }

    public function test_clienti_e_categorie(): void
    {
        $this->actingAs($this->user)->post('/fiscale/clienti', ['causale' => 'Estero SA', 'aliquota_iva' => '0', 'aliquota_ritenuta' => '0'])
            ->assertSessionHasNoErrors();
        $c = FiscalClient::sole();
        $this->assertEqualsWithDelta(1.0, $c->fattore(), 0.0001);

        $this->actingAs($this->user)->delete(route('fiscale.clienti.destroy', $c))->assertRedirect();
        $this->assertModelMissing($c);

        BudgetCategory::create(['categoria' => 'LAVORO', 'nome' => 'Software', 'importo_annuale' => 0, 'importo_mensile' => 0]);
        BudgetCategory::create(['categoria' => 'CASA', 'nome' => 'Luce', 'aziendale' => true, 'importo_annuale' => 0, 'importo_mensile' => 0]);
        $this->actingAs($this->user)->put('/fiscale/categorie', ['categorie' => ['LAVORO']])->assertRedirect();

        $this->assertTrue(BudgetCategory::where('categoria', 'LAVORO')->sole()->aziendale);
        $this->assertFalse(BudgetCategory::where('categoria', 'CASA')->sole()->aziendale);
    }
}
