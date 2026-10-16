<?php

namespace Tests\Unit;

use App\Services\EstrattoConto\Classificatore;
use App\Services\EstrattoConto\Movimento;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ClassificatoreTest extends TestCase
{
    private function movimento(string $descrizione, float $importo, ?string $esercente = null): Movimento
    {
        $data = CarbonImmutable::parse('2026-09-01');
        return new Movimento($data, $data, $importo, 'Carta di debito – Pagamenti', $descrizione, $esercente, false, null, 'test');
    }

    private function classificatore(): Classificatore
    {
        return new Classificatore([
            ['match' => '/Giroconto/i', 'ignora' => 'giroconto'],
            ['match' => '/AUTOGRILL/i', 'max' => 10, 'categoria' => 'Spese accessorie', 'note' => 'Caffe Autogrill'],
            ['match' => '/AUTOGRILL/i', 'categoria' => 'Pranzi/Cene', 'note' => 'Autogrill'],
            ['match' => '/OSTERIA/i', 'categoria' => 'Pranzi/Cene'],
        ]);
    }

    public function test_vince_la_prima_regola_che_corrisponde_rispettando_min_e_max(): void
    {
        $c = $this->classificatore();

        $caffe = $c->classifica($this->movimento('presso AUTOGRILL 0122', -3.99));
        $this->assertSame('Spese accessorie', $caffe->categoria);
        $this->assertSame('Caffe Autogrill', $caffe->note);

        $pranzo = $c->classifica($this->movimento('presso AUTOGRILL 0122', -33.30));
        $this->assertSame('Pranzi/Cene', $pranzo->categoria);
        $this->assertSame('Autogrill', $pranzo->note);
    }

    public function test_nota_di_default_dal_nome_esercente(): void
    {
        $r = $this->classificatore()->classifica($this->movimento('presso TMV OSTERIA - Transazione C-less', -16.50, 'TMV OSTERIA'));

        $this->assertSame('Pranzi/Cene', $r->categoria);
        $this->assertSame('Tmv Osteria', $r->note);
    }

    public function test_ignorati_e_da_classificare(): void
    {
        $c = $this->classificatore();

        $giro = $c->classifica($this->movimento('Giroconto da voi disposto', -1500));
        $this->assertTrue($giro->isIgnorata());
        $this->assertSame('giroconto', $giro->motivoIgnorata);

        $ignoto = $c->classifica($this->movimento('presso NEGOZIO SCONOSCIUTO', -10));
        $this->assertTrue($ignoto->isDaClassificare());
        $this->assertNull($ignoto->categoria);
    }

    public function test_elenca_le_categorie_usate_dalle_regole(): void
    {
        $this->assertSame(['Spese accessorie', 'Pranzi/Cene'], $this->classificatore()->categorieUsate());
    }
}
