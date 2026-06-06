<?php

namespace App\Acciones\Application\Accion\List;

final readonly class ListAccionesQuery
{
    public function __construct(
        public ?bool $onlyActive = null,
    ) {}
}
