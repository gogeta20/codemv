<?php

namespace App\Futbol\Application\Seleccion\GetDiaria;

final readonly class GetSeleccionDiariaQuery
{
    public function __construct(
        public ?\DateTimeImmutable $fecha = null,
    ) {}
}
