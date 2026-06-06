<?php

namespace App\Acciones\Application\Portafolio\Create;

final readonly class CreatePortafolioCommand
{
    public function __construct(
        public string  $nombre,
        public ?string $descripcion = null,
        public bool    $isDefault = false,
    ) {}
}
