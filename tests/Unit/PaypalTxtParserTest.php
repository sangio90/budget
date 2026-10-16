<?php

namespace Tests\Unit;

use App\Services\EstrattoConto\PaypalTxtParser;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PaypalTxtParserTest extends TestCase
{
    private const TESTO = <<<TXT
This week

Trenitalia SPA
+11,60 €
7 Sep . Refund
Last week
Google Cloud Italy S.r.l.
−19,76 €
1 Sep . Automatic Payment
Aug 2026

Negozio Sport s.r.o.
−218,70 €
25 Aug . Payment

Trenitalia SPA
−11,60 €
20 Aug . Payment . Refunded
Jul 2026

Mario Rossi
−1.050,00 €
21 Jul . Money Sent
"Regalo zia Iole"
Repeat
Dec 2025

Negozio Vecchio
−5,00 €
30 Dec . Payment
TXT;

    public function test_legge_esercente_importo_data_tipo_e_nota(): void
    {
        $m = (new PaypalTxtParser())->parseText(self::TESTO, 'test.txt', CarbonImmutable::parse('2026-09-07'));

        $this->assertCount(6, $m);

        $this->assertSame('Trenitalia SPA', $m[0]->esercente);
        $this->assertSame(11.60, $m[0]->importo);
        $this->assertFalse($m[0]->isUscita());
        $this->assertSame('2026-09-07', $m[0]->dataOperazione->toDateString());

        $this->assertSame(-19.76, $m[1]->importo);
        $this->assertSame('2026-09-01', $m[1]->dataOperazione->toDateString());
        $this->assertSame('PayPal Automatic Payment', $m[1]->tipo);

        $this->assertSame(-218.70, $m[2]->importo);
        $this->assertSame('2026-08-25', $m[2]->dataOperazione->toDateString());

        $this->assertStringContainsString('Payment . Refunded', $m[3]->descrizione);

        $this->assertSame(-1050.00, $m[4]->importo);
        $this->assertSame('2026-07-21', $m[4]->dataOperazione->toDateString());
        $this->assertSame('Regalo zia Iole', $m[4]->notaDefault);
        $this->assertSame('PayPal Money Sent: Mario Rossi "Regalo zia Iole"', $m[4]->descrizione);

        $this->assertSame('2025-12-30', $m[5]->dataOperazione->toDateString(), "l'anno arriva dall'intestazione di sezione");
    }
}
