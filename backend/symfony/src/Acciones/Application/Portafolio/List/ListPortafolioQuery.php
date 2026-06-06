<?php

namespace App\Acciones\Application\Portafolio\List;

final readonly class ListPortafolioQuery
{
    public function __construct(
        public ?string $status = null,
    ) {}
}
