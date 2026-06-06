<?php

namespace App\Acciones\Application\Analisis\GetAnalisis;

final readonly class GetAnalisisQuery
{
    public function __construct(
        public string $accionUuid,
    ) {}
}
