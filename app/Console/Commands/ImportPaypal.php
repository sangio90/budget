<?php

namespace App\Console\Commands;

use App\Services\EstrattoConto\PaypalTxtParser;

class ImportPaypal extends ImportSpeseCommand
{
    protected $signature = 'fambudget:import-paypal
                            {file* : Lista attività PayPal copiata dal sito in un file di testo}' . self::OPZIONI;

    protected $description = 'Importa i pagamenti PayPal come spese di budget (regole: config/estratto_conto.php, lista "paypal")';

    protected function leggi(string $file): array
    {
        return (new PaypalTxtParser())->parseFile($file);
    }

    protected function regole(): string
    {
        return 'paypal';
    }
}
