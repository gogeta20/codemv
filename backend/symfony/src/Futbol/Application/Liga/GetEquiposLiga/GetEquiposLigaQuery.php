<?php

namespace App\Futbol\Application\Liga\GetEquiposLiga;

final readonly class GetEquiposLigaQuery
{
    public function __construct(public readonly string $codigo) {}
}
