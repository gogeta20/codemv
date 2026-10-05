<?php

namespace App\Futbol\Application\Copa\Detail;

final readonly class GetCopaPartidoDetalleQuery
{
    public function __construct(
        public string $competition,
        public string $eventId,
    ) {}
}
