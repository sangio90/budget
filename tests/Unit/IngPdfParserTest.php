<?php

namespace Tests\Unit;

use App\Services\EstrattoConto\IngPdfParser;
use PHPUnit\Framework\TestCase;

class IngPdfParserTest extends TestCase
{
    /** Costruisce una riga a colonne fisse come fa `pdftotext -layout`. */
    private function riga(string $data, string $desc, string $tipo = '', string $importo = '', int $descStart = 18, int $typeStart = 73): string
    {
        $line = str_pad($data, $descStart) . str_pad($desc, $typeStart - $descStart) . $tipo;
        if ($importo !== '') {
            $line = str_pad($line, 110 - strlen($importo)) . $importo;
        }
        return rtrim($line);
    }

    /** Intestazione ripetuta a ogni pagina, con le colonne nelle posizioni indicate. */
    private function intestazione(int $descStart, int $typeStart): string
    {
        return implode("\n", [
            str_repeat(' ', $typeStart) . 'Tipo',
            str_pad('Data Contabile', $descStart) . str_pad('Descrizione', 70) . 'Importo (EUR)',
            str_repeat(' ', $typeStart) . 'Transazione',
            '',
        ]);
    }

    private function testo(): string
    {
        $p1 = fn(...$a) => $this->riga(...$a);
        $p2 = fn(string $data, string $desc, string $tipo = '', string $importo = '') => $this->riga($data, $desc, $tipo, $importo, 17, 70);

        return implode("\n", [
            'Lista Movimenti Conto Corrente',
            'Arancio',
            'MARIO ROSSI',
            'IT00X0000000000CC0000000000',
            '',
            '                                        dal 24/08/2026 al 07/09/2026',
            '',
            $this->intestazione(18, 73),
            // pagamento carta, non contabilizzato, esercente su più righe
            $p1('06 settembre', 'Operazione Mastercard del 06/09/2026 alle ore', 'Carta di', '-70,50'),
            $p1('2026 (non', '17:10:26 con Carta xxxxxxxxxxxx1234 Div=EUR', 'debito –'),
            $p1('contabilizzato)', 'Importo in divisa=70.5 / Importo in Euro=70.5 presso', 'Pagamenti'),
            $p1('', 'SUPERMERCATO ROSSI       CREMA'),
            '',
            // bonifico sopra i 1000 euro senza separatore delle migliaia
            $p1('03 settembre', 'Bonifico istantaneo da voi disposto N.', 'Bonifici', '-3500,00'),
            $p1('2026', 'ABC123 A favore di', 'in uscita'),
            $p1('', 'Mario Bianchi IBAN beneficiario'),
            $p1('', 'IT00X0000000000000000000000 Note: Fattura 9'),
            $p1('', '2026'),
            $p1('', 'Saldo Disponibile: 3635.90 EUR'),
            // cambio pagina: colonne spostate
            $this->intestazione(17, 70),
            // addebito SDD: la data operazione è la scadenza
            $p2('04 settembre', 'Addebito SDD CORE Scad. 02/09/2026 Imp. 85.86', 'Addebiti', '-85,86'),
            $p2('2026', 'Creditor id. IT770010000000484960588 Eni Spa - Eni', 'diretti'),
            $p2('', 'Regolamento Monetario Id Mandato'),
            $p2('', '8002118190012163611 Debitore MARIO'),
            $p2('', 'ROSSI Rif. 9002709855500014'),
            $p2('', 'Saldo Disponibile: 3385.91 EUR'),
            '',
            // importo con separatore delle migliaia e blocco spezzato da un cambio pagina
            $p2('27 agosto', 'Operazione Mastercard del 26/08/2026 alle ore', 'Carta di', '-1.234,56'),
            $p2('2026', '13:51 con Carta xxxxxxxxxxxx1234 Div=EUR Importo', 'debito –'),
            $p2('', 'in divisa=1234.56 / Importo in Euro=1234.56 presso', 'Pagamenti'),
            $p2('', 'NEGOZIO GRANDE - Transazione C-less'),
            $this->intestazione(17, 70),
            $p2('', 'Saldo Disponibile: 136.40 EUR'),
            '',
            // entrata
            $p2('24 agosto', 'Giroconto a vostro favore N.', 'Giroconti', '1500,00'),
            $p2('2026', 'XYZ Ordinante MARIO ROSSI Note: Giroconto'),
            $p2('', 'Saldo Disponibile: 512.91 EUR'),
            '',
        ]);
    }

    public function test_legge_tutti_i_movimenti_con_date_importi_ed_esercenti(): void
    {
        $movimenti = (new IngPdfParser())->parseText($this->testo(), 'test');

        $this->assertCount(5, $movimenti);
        $this->assertSame('IT00X0000000000CC0000000000', $movimenti[0]->iban);

        $carta = $movimenti[0];
        $this->assertSame('2026-09-06', $carta->dataContabile->toDateString());
        $this->assertSame('2026-09-06', $carta->dataOperazione->toDateString());
        $this->assertSame(-70.50, $carta->importo);
        $this->assertTrue($carta->nonContabilizzato);
        $this->assertSame('SUPERMERCATO ROSSI CREMA', $carta->esercente);
        $this->assertSame('Carta di debito – Pagamenti', $carta->tipo);

        $bonifico = $movimenti[1];
        $this->assertSame(-3500.00, $bonifico->importo);
        $this->assertSame('2026-09-03', $bonifico->dataOperazione->toDateString());
        $this->assertSame('Mario Bianchi', $bonifico->esercente);
        $this->assertStringContainsString('Note: Fattura 9 2026', $bonifico->descrizione);
        $this->assertStringNotContainsString('Saldo Disponibile', $bonifico->descrizione);
        $this->assertFalse($bonifico->nonContabilizzato);

        $sdd = $movimenti[2];
        $this->assertSame(-85.86, $sdd->importo);
        $this->assertSame('2026-09-04', $sdd->dataContabile->toDateString());
        $this->assertSame('2026-09-02', $sdd->dataOperazione->toDateString());
        $this->assertSame('Eni Spa - Eni Regolamento Monetario', $sdd->esercente);
        $this->assertSame('Addebiti diretti', $sdd->tipo);

        $grande = $movimenti[3];
        $this->assertSame(-1234.56, $grande->importo);
        $this->assertSame('2026-08-26', $grande->dataOperazione->toDateString());
        $this->assertSame('NEGOZIO GRANDE', $grande->esercente);
        $this->assertStringNotContainsString('Saldo Disponibile', $grande->descrizione);

        $entrata = $movimenti[4];
        $this->assertSame(1500.00, $entrata->importo);
        $this->assertFalse($entrata->isUscita());
    }
}
