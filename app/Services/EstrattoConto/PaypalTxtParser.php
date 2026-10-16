<?php

namespace App\Services\EstrattoConto;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Legge la lista attività di PayPal copiata dalla pagina web in un file di testo.
 *
 * Ogni voce occupa tre righe: esercente, importo ("−218,70 €" oppure "+11,60 €"),
 * "25 Aug . Payment" (data e tipo), più un'eventuale riga tra virgolette con la
 * nota dell'invio di denaro. Le intestazioni di sezione ("This week", "Aug 2026")
 * servono solo a ricavare l'anno.
 */
class PaypalTxtParser
{
    private const MESI = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        'gen' => 1, 'mag' => 5, 'giu' => 6, 'lug' => 7, 'ago' => 8, 'set' => 9, 'ott' => 10, 'dic' => 12,
    ];

    /** @return Movimento[] */
    public function parseFile(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File non trovato: {$path}");
        }
        return $this->parseText(file_get_contents($path), basename($path));
    }

    /** @return Movimento[] */
    public function parseText(string $testo, string $sorgente = 'paypal.txt', ?CarbonImmutable $oggi = null): array
    {
        $oggi ??= CarbonImmutable::today();
        $anno = $oggi->year;
        $righe = array_values(array_filter(array_map('trim', preg_split('/\R/u', $testo)), fn($l) => $l !== ''));

        $movimenti = [];
        $precedente = null;
        for ($i = 0; $i < count($righe); $i++) {
            $riga = $righe[$i];

            if (preg_match('/^([A-Za-z]{3})[a-z]* (\d{4})$/u', $riga, $m) && isset(self::MESI[strtolower($m[1])])) {
                $anno = (int) $m[2];
                $precedente = null;
                continue;
            }

            $importoRiga = str_replace(['−', '–'], '-', $riga);
            if (!preg_match('/^([+-])?\s*(\d+(?:\.\d{3})*,\d{2})\s*€$/u', $importoRiga, $m)) {
                $precedente = $riga;
                continue;
            }
            if ($precedente === null) {
                throw new RuntimeException("Importo senza esercente in {$sorgente}: \"{$riga}\"");
            }

            $importo = (float) str_replace(['.', ','], ['', '.'], $m[2]);
            $importo = ($m[1] ?? '-') === '+' ? $importo : -$importo;

            $dataRiga = $righe[++$i] ?? '';
            if (!preg_match('/^(\d{1,2}) ([A-Za-z]{3})[a-z]*\s*\.\s*(.+)$/u', $dataRiga, $d) || !isset(self::MESI[strtolower($d[2])])) {
                throw new RuntimeException("Riga data non riconosciuta in {$sorgente}: \"{$dataRiga}\"");
            }
            $mese = self::MESI[strtolower($d[2])];
            $annoVoce = ($mese > $oggi->month && $anno === $oggi->year) ? $anno - 1 : $anno;
            $data = CarbonImmutable::create($annoVoce, $mese, (int) $d[1])->startOfDay();
            $tipo = trim(preg_replace('/\s*\.\s*/', ' . ', $d[3]));

            $nota = null;
            if (isset($righe[$i + 1]) && preg_match('/^"(.+)"$/u', $righe[$i + 1], $n)) {
                $nota = $n[1];
                $i++;
            }

            $movimenti[] = new Movimento(
                dataContabile: $data,
                dataOperazione: $data,
                importo: $importo,
                tipo: 'PayPal ' . $tipo,
                descrizione: "PayPal {$tipo}: {$precedente}" . ($nota ? " \"{$nota}\"" : ''),
                esercente: $precedente,
                nonContabilizzato: false,
                iban: null,
                sorgente: $sorgente,
                notaDefault: $nota,
            );
            $precedente = null;
        }

        return $movimenti;
    }
}
