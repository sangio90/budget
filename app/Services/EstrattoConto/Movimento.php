<?php

namespace App\Services\EstrattoConto;

use Carbon\CarbonImmutable;

/**
 * Un movimento estratto dalla "Lista Movimenti" di ING.
 */
class Movimento
{
    public function __construct(
        /** Data contabile riportata nella prima colonna. */
        public readonly CarbonImmutable $dataContabile,
        /** Data effettiva dell'operazione (data carta, scadenza SDD) o data contabile se assente. */
        public readonly CarbonImmutable $dataOperazione,
        /** Importo con segno: negativo per le uscite. */
        public readonly float $importo,
        /** Colonna "Tipo Transazione", es. "Carta di debito – Pagamenti". */
        public readonly string $tipo,
        /** Descrizione completa (senza "Saldo Disponibile"). */
        public readonly string $descrizione,
        /** Esercente / creditore / beneficiario, quando riconoscibile. */
        public readonly ?string $esercente,
        public readonly bool $nonContabilizzato,
        public readonly ?string $iban,
        public readonly string $sorgente,
        /** Nota proposta dalla sorgente (es. titolo articolo Amazon); se null si usa l'esercente. */
        public readonly ?string $notaDefault = null,
    ) {}

    public function isUscita(): bool
    {
        return $this->importo < 0;
    }

    /** Importo in valore assoluto, come va salvato nelle spese. */
    public function importoAssoluto(): float
    {
        return round(abs($this->importo), 2);
    }
}
