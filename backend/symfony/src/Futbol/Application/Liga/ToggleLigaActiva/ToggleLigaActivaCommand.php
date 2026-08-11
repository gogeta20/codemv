<?php

namespace App\Futbol\Application\Liga\ToggleLigaActiva;

final class ToggleLigaActivaCommand
{
    public function __construct(
        public readonly string $codigoEspn,
        public readonly bool $activa,
        public readonly string $nombre,
        public readonly string $pais,
    ) {}
}
