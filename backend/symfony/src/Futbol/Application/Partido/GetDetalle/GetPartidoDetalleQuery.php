<?php

namespace App\Futbol\Application\Partido\GetDetalle;

final readonly class GetPartidoDetalleQuery
{
    public function __construct(
        public string $uuid,
    ) {}
}
