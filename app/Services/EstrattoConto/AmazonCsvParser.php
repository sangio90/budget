<?php

namespace App\Services\EstrattoConto;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Legge il report ordini di Amazon Business (Ordini → Scarica report, CSV).
 *
 * Una riga per articolo. Ogni articolo diventa un movimento a sé, con importo
 * "Totale netto dell'articolo": gli articoli di uno stesso pagamento sommano
 * all'importo addebitato in banca, il cui riferimento ("ID di riferimento del
 * pagamento") compare anche in coda alla descrizione dell'SDD sull'estratto conto.
 */
class AmazonCsvParser
{
    private const COLONNE = [
        'dataOrdine'    => 'Data ordine',
        'ordine'        => "Numero dell'ordine",
        'stato'         => 'Stato ordine',
        'riferimento'   => 'ID di riferimento del pagamento',
        'dataPagamento' => 'Data di pagamento',
        'metodo'        => 'Tipo di metodo di pagamento',
        'asin'          => 'ASIN',
        'titolo'        => 'Titolo',
        'quantita'      => 'Quantità articoli',
        'totale'        => "Totale netto dell'articolo",
        'venditore'     => 'Nome venditore',
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
    public function parseText(string $csv, string $sorgente = 'amazon.csv'): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv); // BOM
        $righe = array_map('str_getcsv', preg_split('/\R/', trim($csv)));
        $intestazione = array_map('trim', array_shift($righe));

        $indici = [];
        foreach (self::COLONNE as $chiave => $nome) {
            $i = array_search($nome, $intestazione, true);
            if ($i === false) {
                throw new RuntimeException("Colonna \"{$nome}\" non trovata in {$sorgente}: non sembra il report ordini di Amazon Business.");
            }
            $indici[$chiave] = $i;
        }

        $movimenti = [];
        foreach ($righe as $riga) {
            if (count($riga) < count($intestazione)) {
                continue;
            }
            $r = array_map(fn($i) => trim($riga[$i] ?? ''), $indici);
            if ($r['riferimento'] === '' || $r['riferimento'] === 'N/A' || $r['totale'] === '') {
                continue; // ordine non ancora pagato o annullato
            }
            if (preg_match('/annullat/i', $r['stato'])) {
                continue;
            }

            $importo = (float) str_replace(['.', ','], ['', '.'], $r['totale']);
            $titolo = preg_replace('/\s+/u', ' ', $r['titolo']);

            $movimenti[] = new Movimento(
                dataContabile: $this->data($r['dataPagamento'] ?: $r['dataOrdine']),
                dataOperazione: $this->data($r['dataOrdine']),
                importo: -abs($importo),
                tipo: 'Amazon ' . $r['metodo'],
                descrizione: sprintf(
                    'Amazon ordine %s rif. %s ASIN %s: %s (venditore: %s)',
                    $r['ordine'], $r['riferimento'], $r['asin'], $titolo, $r['venditore'],
                ),
                esercente: $titolo,
                nonContabilizzato: false,
                iban: null,
                sorgente: $sorgente,
                notaDefault: $this->notaBreve($titolo, (int) $r['quantita']),
            );
        }

        return $movimenti;
    }

    private function data(string $ggmmaaaa): CarbonImmutable
    {
        if (!preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $ggmmaaaa, $m)) {
            throw new RuntimeException("Data non riconosciuta nel report Amazon: \"{$ggmmaaaa}\"");
        }
        return CarbonImmutable::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
    }

    /**
     * Titolo accorciato per una nota leggibile: prende i segmenti del titolo
     * (separati da " - ", "," o " | ") finché non arriva a 20 caratteri, poi
     * taglia a 40 senza spezzare le parole.
     */
    private function notaBreve(string $titolo, int $quantita): string
    {
        $breve = '';
        foreach (preg_split('/ - |,| \| /u', $titolo) as $segmento) {
            $breve = trim($breve . ' ' . trim($segmento));
            if (mb_strlen($breve) >= 20) {
                break;
            }
        }
        if (mb_strlen($breve) > 40) {
            $taglio = mb_strrpos(mb_substr($breve, 0, 41), ' ');
            $breve = trim(mb_substr($breve, 0, $taglio ?: 40));
        }

        return $quantita > 1 ? "{$breve} x{$quantita}" : $breve;
    }
}
