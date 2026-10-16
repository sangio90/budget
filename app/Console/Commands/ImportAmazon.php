<?php

namespace App\Console\Commands;

use App\Services\EstrattoConto\AmazonCsvParser;

class ImportAmazon extends ImportSpeseCommand
{
    protected $signature = 'fambudget:import-amazon
                            {file* : Report ordini CSV di Amazon Business (Ordini → Scarica report)}' . self::OPZIONI;

    protected $description = 'Importa gli articoli del report ordini Amazon come spese di budget (regole: config/estratto_conto.php, lista "amazon")';

    protected function leggi(string $file): array
    {
        return (new AmazonCsvParser())->parseFile($file);
    }

    protected function regole(): string
    {
        return 'amazon';
    }
}
