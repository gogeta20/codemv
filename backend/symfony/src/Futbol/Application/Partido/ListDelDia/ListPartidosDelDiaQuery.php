<?php

namespace App\Futbol\Application\Partido\ListDelDia;

final readonly class ListPartidosDelDiaQuery
{
    public function __construct(
        public ?\DateTimeImmutable $fecha = null,
    ) {}
}
