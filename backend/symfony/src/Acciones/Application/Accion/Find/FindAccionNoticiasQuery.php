<?php

namespace App\Acciones\Application\Accion\Find;

final readonly class FindAccionNoticiasQuery
{
    public function __construct(
        public string $uuid,
    ) {}
}
