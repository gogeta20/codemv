<?php

namespace App\Acciones\Application\Portafolio\UpdateAccion;

final readonly class UpdateAccionCommand
{
    public function __construct(
        public string  $entryUuid,
        public ?string $status = null,
        public ?float  $precioReferencia = null,
        public ?string $notas = null,
    ) {}
}
