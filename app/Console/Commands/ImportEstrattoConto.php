<?php

namespace App\Console\Commands;

use App\Services\EstrattoConto\IngPdfParser;

class ImportEstrattoConto extends ImportSpeseCommand
{
    protected $signature = 'fambudget:import-estratto
                            {file* : PDF "Lista Movimenti" di ING, oppure il testo estratto con pdftotext -layout}' . self::OPZIONI;

    protected $description = 'Importa le uscite di un estratto conto ING come spese di budget (regole: config/estratto_conto.php, lista "regole")';

    protected function leggi(string $file): array
    {
        return (new IngPdfParser())->parseFile($file);
    }

    protected function regole(): string
    {
        return 'regole';
    }
}
