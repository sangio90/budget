<?php

namespace App\Services\EstrattoConto;

class Classificazione
{
    private function __construct(
        public readonly ?string $categoria,
        public readonly ?string $note,
        public readonly ?string $motivoIgnorata,
    ) {}

    public static function categoria(string $categoria, ?string $note): self
    {
        return new self($categoria, $note, null);
    }

    public static function ignorata(string $motivo): self
    {
        return new self(null, null, $motivo);
    }

    public static function daClassificare(): self
    {
        return new self(null, null, null);
    }

    public function isIgnorata(): bool
    {
        return $this->motivoIgnorata !== null;
    }

    public function isDaClassificare(): bool
    {
        return $this->categoria === null && $this->motivoIgnorata === null;
    }
}
