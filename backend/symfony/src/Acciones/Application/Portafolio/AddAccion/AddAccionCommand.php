<?php

namespace App\Acciones\Application\Portafolio\AddAccion;

final readonly class AddAccionCommand
{
    public function __construct(
        public string  $portafolioUuid,
        public string  $accionUuid,
        public string  $status = 'watchlist',
        public ?float  $precioReferencia = null,
        public ?string $notas = null,
    ) {}
}
