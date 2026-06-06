<?php

namespace App\Acciones\Application\Portafolio\Update;

final readonly class UpdatePortafolioCommand
{
    public function __construct(
        public string  $uuid,
        public ?string $nombre = null,
        public ?string $descripcion = null,
        public ?bool   $isDefault = null,
    ) {}
}
