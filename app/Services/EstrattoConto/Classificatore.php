<?php

namespace App\Services\EstrattoConto;

/**
 * Applica le regole di config/estratto_conto.php a un movimento.
 * Vince la prima regola che corrisponde.
 */
class Classificatore
{
    /** @param array<int, array<string, mixed>> $regole */
    public function __construct(private readonly array $regole) {}

    /** @param string $chiave lista di regole in config/estratto_conto.php (regole, amazon, paypal...) */
    public static function fromConfig(string $chiave = 'regole'): self
    {
        return new self(config("estratto_conto.{$chiave}", []));
    }

    /** Nomi di categoria referenziati dalle regole (per validarli contro il DB). */
    public function categorieUsate(): array
    {
        return array_values(array_unique(array_filter(array_column($this->regole, 'categoria'))));
    }

    public function classifica(Movimento $mov): Classificazione
    {
        $importo = $mov->importoAssoluto();

        foreach ($this->regole as $regola) {
            if (!preg_match($regola['match'], $mov->descrizione)) {
                continue;
            }
            if (isset($regola['min']) && $importo < $regola['min']) {
                continue;
            }
            if (isset($regola['max']) && $importo > $regola['max']) {
                continue;
            }
            if (!empty($regola['ignora'])) {
                return Classificazione::ignorata($regola['ignora']);
            }

            return Classificazione::categoria(
                $regola['categoria'],
                $regola['note'] ?? $this->notaDaEsercente($mov),
            );
        }

        return Classificazione::daClassificare();
    }

    private function notaDaEsercente(Movimento $mov): ?string
    {
        if ($mov->notaDefault !== null) {
            return mb_substr($mov->notaDefault, 0, 60);
        }
        if ($mov->esercente === null) {
            return null;
        }
        $nota = mb_convert_case(mb_strtolower($mov->esercente), MB_CASE_TITLE, 'UTF-8');

        return mb_substr(rtrim($nota, ' -'), 0, 60);
    }
}
