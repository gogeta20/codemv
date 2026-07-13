<?php

namespace App\Futbol\Application\Equipo\GetAnalisis;

final readonly class GetEquipoAnalisisQuery
{
    public function __construct(
        public string $teamId,
        public string $liga,
        public ?int $season = null,
    ) {}
}
