<?php

namespace App\Acciones\Application\Accion\Create;

final readonly class CreateAccionCommand
{
    public function __construct(
        public string  $symbol,
        public string  $name,
        public string  $type,
        public string  $source = 'manual',
        public float   $alertThresholdPct = 5.0,
        public ?string $sector = null,
        public ?string $industry = null,
        public ?string $exchange = null,
    ) {}
}
