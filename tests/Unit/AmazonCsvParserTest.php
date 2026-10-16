<?php

namespace Tests\Unit;

use App\Services\EstrattoConto\AmazonCsvParser;
use PHPUnit\Framework\TestCase;

class AmazonCsvParserTest extends TestCase
{
    private function csv(): string
    {
        $h = ['Data ordine', "Numero dell'ordine", 'Stato ordine', 'ID di riferimento del pagamento', 'Data di pagamento',
            'Tipo di metodo di pagamento', 'ASIN', 'Titolo', 'Quantità articoli', "Totale netto dell'articolo", 'Nome venditore'];
        $righe = [
            ['22/08/2026', '407-1', 'Chiuso', 'REF1', '23/08/2026', 'Direct Debit', 'B001', 'SwitchBot Termometro igrometro, Bluetooth', '1', '24,91', 'WONDERLABS'],
            ['22/08/2026', '407-1', 'Chiuso', 'REF1', '23/08/2026', 'Direct Debit', 'B002', 'MisterVac | sacchetti per aspirapolvere 20 pezzi', '2', '20,74', 'MisterVac'],
            ['27/08/2026', '407-2', 'Chiuso', 'REF2', '28/08/2026', 'Direct Debit', 'B003', "Naruto. Il mito (Vol. 45)", '1', '5,60', 'Amazon EU'],
            ['01/09/2026', '407-3', 'Annullato', 'REF3', '', 'Direct Debit', 'B004', 'Ordine annullato', '1', '9,99', 'X'],
            ['02/09/2026', '407-4', 'In corso', 'N/A', 'N/A', 'Direct Debit', 'B005', 'Non ancora pagato', '1', '19,99', 'X'],
        ];
        $out = fopen('php://memory', 'w+');
        fputcsv($out, $h);
        foreach ($righe as $r) {
            fputcsv($out, $r);
        }
        rewind($out);
        return "\xEF\xBB\xBF" . stream_get_contents($out);
    }

    public function test_un_movimento_per_articolo_con_titolo_abbreviato_come_nota(): void
    {
        $m = (new AmazonCsvParser())->parseText($this->csv(), 'test.csv');

        $this->assertCount(3, $m, 'ordini annullati o non pagati vengono saltati');

        $this->assertSame('2026-08-22', $m[0]->dataOperazione->toDateString());
        $this->assertSame('2026-08-23', $m[0]->dataContabile->toDateString());
        $this->assertSame(-24.91, $m[0]->importo);
        $this->assertSame('SwitchBot Termometro igrometro', $m[0]->notaDefault);
        $this->assertStringContainsString('rif. REF1', $m[0]->descrizione);
        $this->assertStringContainsString('ASIN B001', $m[0]->descrizione);

        $this->assertSame('MisterVac sacchetti per aspirapolvere 20 x2', $m[1]->notaDefault);
        $this->assertSame(-20.74, $m[1]->importo);

        $this->assertSame('Naruto. Il mito (Vol. 45)', $m[2]->notaDefault);
        $this->assertSame(-5.60, $m[2]->importo);
    }

    public function test_errore_chiaro_se_manca_una_colonna(): void
    {
        $this->expectExceptionMessage('Colonna "Titolo" non trovata');
        $intestazione = "Data ordine,Numero dell'ordine,Stato ordine,ID di riferimento del pagamento,Data di pagamento,"
            . "Tipo di metodo di pagamento,ASIN,Quantità articoli,Totale netto dell'articolo,Nome venditore";
        (new AmazonCsvParser())->parseText($intestazione . "\n01/01/2026,407-1,Chiuso,REF,01/01/2026,Direct Debit,B0,1,\"1,00\",X\n");
    }
}
