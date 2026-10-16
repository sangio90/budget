<?php

namespace Tests\Feature;

use App\Models\ExtraIncome;
use App\Models\User;
use Database\Seeders\ExtraIncomesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraIncomeTest extends TestCase
{
    use RefreshDatabase;

    private function entrata(User $user, array $attrs = []): ExtraIncome
    {
        return ExtraIncome::create($attrs + [
            'user_id'   => $user->id,
            'categoria' => 'Affitto',
            'immobile'  => 'Agnadello',
            'importo'   => 500,
            'data'      => now()->year . '-02-02',
            'causale'   => 'Inquilino',
            'note'      => 'Fitto febbraio',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/entrate-extra')->assertRedirect('/login');
    }

    public function test_index_shows_entries_and_totals(): void
    {
        $user = User::factory()->create();
        $this->entrata($user);
        $this->entrata($user, ['categoria' => 'Cedole', 'immobile' => null, 'importo' => 70, 'causale' => 'BTP Valore']);

        $this->actingAs($user)
            ->get('/entrate-extra')
            ->assertOk()
            ->assertSee('Entrate Extra')
            ->assertSee('Inquilino')
            ->assertSee('BTP Valore')
            ->assertSee('570,00 €', false);
    }

    public function test_index_can_be_filtered_by_categoria_and_immobile(): void
    {
        $user = User::factory()->create();
        $this->entrata($user, ['causale' => 'Affitto Agnadello']);
        $this->entrata($user, ['immobile' => 'Ombriano', 'causale' => 'Affitto Ombriano']);
        $this->entrata($user, ['categoria' => 'Interessi conto', 'immobile' => null, 'causale' => 'Conto deposito']);

        $this->actingAs($user)
            ->get('/entrate-extra?immobile=Ombriano')
            ->assertOk()
            ->assertViewHas('entrate', fn($e) => $e->count() === 1 && $e->first()->causale === 'Affitto Ombriano');

        $this->actingAs($user)
            ->get('/entrate-extra?categoria=' . urlencode('Interessi conto'))
            ->assertOk()
            ->assertViewHas('entrate', fn($e) => $e->count() === 1 && $e->first()->causale === 'Conto deposito');
    }

    public function test_index_does_not_show_other_users_entries(): void
    {
        $user  = User::factory()->create();
        $altro = User::factory()->create();
        $this->entrata($altro, ['causale' => 'Non mia']);

        $this->actingAs($user)
            ->get('/entrate-extra')
            ->assertOk()
            ->assertDontSee('Non mia');
    }

    public function test_entry_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/entrate-extra', [
                'categoria' => 'Fotovoltaico (GSE)',
                'immobile'  => '',
                'importo'   => '121.35',
                'data'      => '2026-03-27',
                'causale'   => 'GSE conv. RID438169',
                'note'      => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('extra-incomes.index', ['anno' => 2026]));

        $entrata = ExtraIncome::sole();
        $this->assertSame($user->id, $entrata->user_id);
        $this->assertSame('Fotovoltaico (GSE)', $entrata->categoria);
        $this->assertNull($entrata->immobile);
        $this->assertEquals(121.35, (float) $entrata->importo);
        $this->assertSame('2026-03-27', $entrata->data->format('Y-m-d'));
    }

    public function test_negative_amount_is_allowed_but_zero_is_not(): void
    {
        $user = User::factory()->create();
        $dati = ['categoria' => 'Interessi conto', 'data' => '2026-05-06', 'causale' => 'BBVA conto deposito'];

        $this->actingAs($user)->post('/entrate-extra', $dati + ['importo' => '-43.16'])
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/entrate-extra', $dati + ['importo' => '0'])
            ->assertSessionHasErrors('importo');

        $this->assertSame(1, ExtraIncome::count());
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/entrate-extra', [])
            ->assertSessionHasErrors(['categoria', 'importo', 'data', 'causale']);

        $this->assertSame(0, ExtraIncome::count());
    }

    public function test_edit_page_is_displayed(): void
    {
        $user    = User::factory()->create();
        $entrata = $this->entrata($user);

        $this->actingAs($user)
            ->get(route('extra-incomes.edit', $entrata))
            ->assertOk()
            ->assertSee('Modifica Entrata Extra')
            ->assertSee('Inquilino');
    }

    public function test_entry_can_be_updated_and_redirects_back_to_filtered_list(): void
    {
        $user    = User::factory()->create();
        $entrata = $this->entrata($user);
        $back    = route('extra-incomes.index', ['anno' => now()->year, 'immobile' => 'Agnadello']);

        $this->actingAs($user)
            ->put(route('extra-incomes.update', $entrata), [
                'categoria' => 'Affitto',
                'immobile'  => 'Agnadello',
                'importo'   => '550',
                'data'      => now()->year . '-02-03',
                'causale'   => 'Inquilino',
                'note'      => 'Aggiornato',
                'back'      => $back,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($back);

        $entrata->refresh();
        $this->assertEquals(550, (float) $entrata->importo);
        $this->assertSame('Aggiornato', $entrata->note);
    }

    public function test_update_ignores_external_back_url(): void
    {
        $user    = User::factory()->create();
        $entrata = $this->entrata($user);

        $this->actingAs($user)
            ->put(route('extra-incomes.update', $entrata), [
                'categoria' => 'Affitto',
                'importo'   => '500',
                'data'      => '2026-02-02',
                'causale'   => 'Inquilino',
                'back'      => 'https://example.com/phishing',
            ])
            ->assertRedirect(route('extra-incomes.index', ['anno' => 2026]));
    }

    public function test_entry_can_be_deleted(): void
    {
        $user    = User::factory()->create();
        $entrata = $this->entrata($user);

        $this->actingAs($user)
            ->delete(route('extra-incomes.destroy', $entrata))
            ->assertRedirect();

        $this->assertModelMissing($entrata);
    }

    public function test_other_users_entries_are_forbidden(): void
    {
        $user    = User::factory()->create();
        $entrata = $this->entrata(User::factory()->create());

        $this->actingAs($user)->get(route('extra-incomes.edit', $entrata))->assertForbidden();
        $this->actingAs($user)->put(route('extra-incomes.update', $entrata), [
            'categoria' => 'Affitto', 'importo' => '1', 'data' => '2026-01-01', 'causale' => 'x',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('extra-incomes.destroy', $entrata))->assertForbidden();

        $this->assertModelExists($entrata);
    }

    public function test_dashboard_and_statistiche_show_extra_incomes(): void
    {
        $user = User::factory()->create();
        $this->entrata($user, ['importo' => 1234, 'causale' => 'Affitto test']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Entrate extra')
            ->assertSee('1.234 €', false);

        $this->actingAs($user)
            ->get('/statistiche')
            ->assertOk()
            ->assertSee('Entrate extra')
            ->assertSee('Per immobile');
    }

    public function test_seeder_imports_records_once(): void
    {
        User::factory()->create();

        $this->seed(ExtraIncomesSeeder::class);
        $this->assertSame(44, ExtraIncome::count());

        // Rilanciato non duplica
        $this->seed(ExtraIncomesSeeder::class);
        $this->assertSame(44, ExtraIncome::count());

        $totali = ExtraIncome::all()->groupBy('categoria')->map(fn($g) => round($g->sum('importo'), 2));
        $this->assertEquals(7430.00, $totali['Affitto']);
        $this->assertEquals(424.00, $totali['Rimborso spese immobile']);
        $this->assertEquals(243.76, $totali['Fotovoltaico (GSE)']);
        $this->assertEquals(70.00, $totali['Cedole']);
        $this->assertEquals(358.32, $totali['Interessi conto']);

        $riepilogo = ExtraIncome::riepilogoAnno(1, 2026);
        $this->assertEquals(8526.08, $riepilogo['totale']);
        $this->assertEquals(4500.00, $riepilogo['perImmobile']['Agnadello']);
    }
}
