<?php

namespace App\Services\EstrattoConto;

use Carbon\CarbonImmutable;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Legge la "Lista Movimenti Conto Corrente" di ING (Conto Arancio).
 *
 * Accetta il PDF originale (serve `pdftotext`, pacchetto poppler) oppure il testo
 * già estratto con `pdftotext -layout file.pdf file.txt`.
 *
 * Il layout è a colonne: data contabile | descrizione | tipo transazione | importo.
 * Ogni movimento inizia con una riga "GG mese" in prima colonna e prosegue finché
 * non inizia il movimento successivo; le intestazioni ripetute a ogni pagina
 * ("Tipo", "Data Contabile ...", "Transazione") vengono scartate ma servono a
 * ricalcolare le posizioni delle colonne, che cambiano leggermente da pagina a pagina.
 */
class IngPdfParser
{
    private const MESI = [
        'gennaio' => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4,
        'maggio' => 5, 'giugno' => 6, 'luglio' => 7, 'agosto' => 8,
        'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12,
    ];

    /** @return Movimento[] */
    public function parseFile(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File non trovato: {$path}");
        }

        $text = str_ends_with(strtolower($path), '.pdf')
            ? $this->pdfToText($path)
            : file_get_contents($path);

        return $this->parseText($text, basename($path));
    }

    /** @return Movimento[] */
    public function parseText(string $text, string $sorgente = 'testo'): array
    {
        $iban = null;
        $descStart = null;
        $typeStart = null;
        $blocks = [];
        $current = null;

        foreach (preg_split('/\R/u', $text) as $line) {
            $trim = trim($line);

            if ($iban === null && preg_match('/^(IT\d{2}[A-Z]\d{10}[A-Z0-9]{12})$/', $trim, $m)) {
                $iban = $m[1];
                continue;
            }
            // Intestazioni di pagina: aggiornano le colonne e vengono scartate.
            if (preg_match('/^(\s+)Tipo$/u', rtrim($line), $m)) {
                $typeStart = mb_strlen($m[1]);
                continue;
            }
            if (str_starts_with($line, 'Data Contabile')) {
                $descStart = mb_strpos($line, 'Descrizione');
                continue;
            }
            if ($trim === '' || $trim === 'Transazione') {
                continue;
            }

            if (preg_match('/^(\d{2}) ([a-zà]+)\s/u', $line, $m) && isset(self::MESI[$m[2]])) {
                if ($current !== null) {
                    $blocks[] = $current;
                }
                $current = ['day' => (int) $m[1], 'month' => self::MESI[$m[2]], 'lines' => []];
            }

            if ($current === null) {
                continue; // testo di intestazione prima del primo movimento
            }
            if ($descStart === null || $typeStart === null) {
                throw new RuntimeException("Colonne non riconosciute in {$sorgente}: manca l'intestazione della tabella.");
            }
            $current['lines'][] = [$line, $descStart, $typeStart];
        }
        if ($current !== null) {
            $blocks[] = $current;
        }

        return array_map(fn(array $b) => $this->buildMovimento($b, $iban, $sorgente), $blocks);
    }

    private function buildMovimento(array $block, ?string $iban, string $sorgente): Movimento
    {
        $dateCol = [];
        $descCol = [];
        $typeCol = [];
        $importo = null;

        foreach ($block['lines'] as $i => [$line, $descStart, $typeStart]) {
            $dateCol[] = trim(mb_substr($line, 0, $descStart));
            $descCol[] = trim(mb_substr($line, $descStart, $typeStart - $descStart));
            $rest = mb_substr($line, $typeStart);
            if ($i === 0) {
                if (!preg_match('/(-?\d[\d.]*,\d{2})\s*$/', $rest, $m)) {
                    throw new RuntimeException("Importo non trovato in {$sorgente}: \"" . trim($line) . '"');
                }
                $importo = (float) str_replace(['.', ','], ['', '.'], $m[1]);
                $rest = preg_replace('/-?\d[\d.]*,\d{2}\s*$/', '', $rest);
            }
            $typeCol[] = trim($rest);
        }

        $dateText = implode(' ', $dateCol);
        if (!preg_match('/\b(\d{4})\b/', $dateText, $m)) {
            throw new RuntimeException("Anno non trovato in {$sorgente}: \"{$dateText}\"");
        }
        $dataContabile = CarbonImmutable::create((int) $m[1], $block['month'], $block['day'])->startOfDay();

        $descrizione = $this->normalizeSpaces(implode(' ', array_filter($descCol)));
        $descrizione = trim(preg_replace('/\s*Saldo Disponibile:.*$/u', '', $descrizione));
        $tipo = $this->normalizeSpaces(implode(' ', array_filter($typeCol)));

        return new Movimento(
            dataContabile: $dataContabile,
            dataOperazione: $this->dataOperazione($descrizione) ?? $dataContabile,
            importo: $importo,
            tipo: $tipo,
            descrizione: $descrizione,
            esercente: $this->esercente($descrizione),
            nonContabilizzato: str_contains($dateText, 'non contabilizzato'),
            iban: $iban,
            sorgente: $sorgente,
        );
    }

    private function dataOperazione(string $descrizione): ?CarbonImmutable
    {
        // Pagamenti con carta: "Operazione Mastercard del 03/09/2026 ..."
        // Addebiti diretti:    "Addebito SDD CORE Scad. 04/09/2026 ..."
        if (preg_match('/(?:\bdel|Scad\.) (\d{2})\/(\d{2})\/(\d{4})/', $descrizione, $m)) {
            return CarbonImmutable::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }
        return null;
    }

    private function esercente(string $descrizione): ?string
    {
        if (preg_match('/\bpresso (.+?)(?: - Transazione C-less)?$/u', $descrizione, $m)) {
            return $this->normalizeSpaces($m[1]);
        }
        if (preg_match('/Creditor id\. \S+ (.+?) Id Mandato/u', $descrizione, $m)) {
            return $this->normalizeSpaces($m[1]);
        }
        if (preg_match('/A favore di (.+?) IBAN/u', $descrizione, $m)) {
            return $this->normalizeSpaces($m[1]);
        }
        return null;
    }

    private function normalizeSpaces(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    private function pdfToText(string $path): string
    {
        if ((new ExecutableFinder())->find('pdftotext') === null) {
            throw new RuntimeException(
                "pdftotext non trovato: installa poppler (brew install poppler / apt install poppler-utils) " .
                "oppure passa il testo estratto con `pdftotext -layout file.pdf file.txt`."
            );
        }
        $process = new Process(['pdftotext', '-layout', $path, '-']);
        $process->mustRun();

        return $process->getOutput();
    }
}
