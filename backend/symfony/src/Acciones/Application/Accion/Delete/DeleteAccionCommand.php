<?php

namespace App\Acciones\Application\Accion\Delete;

final readonly class DeleteAccionCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
