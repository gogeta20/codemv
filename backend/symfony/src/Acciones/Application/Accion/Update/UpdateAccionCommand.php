<?php

namespace App\Acciones\Application\Accion\Update;

final readonly class UpdateAccionCommand
{
    public function __construct(
        public string  $uuid,
        public ?string $name = null,
        public ?bool   $isActive = null,
        public ?float  $alertThresholdPct = null,
        public ?string $sector = null,
        public ?string $industry = null,
        public ?string $exchange = null,
    ) {}
}
