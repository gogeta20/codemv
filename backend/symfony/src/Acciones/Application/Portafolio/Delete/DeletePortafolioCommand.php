<?php

namespace App\Acciones\Application\Portafolio\Delete;

final readonly class DeletePortafolioCommand
{
    public function __construct(public string $uuid) {}
}
