<?php

namespace App\Futbol\Application\Seleccion\GetMundial;

final readonly class GetMundialSeleccionQuery
{
    public function __construct(
        public ?\DateTimeImmutable $fecha = null,
    ) {}
}
