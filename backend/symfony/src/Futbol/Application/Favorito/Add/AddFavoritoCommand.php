<?php

namespace App\Futbol\Application\Favorito\Add;

final class AddFavoritoCommand
{
    public function __construct(
        public readonly string $espnTeamId,
        public readonly string $espnLigaCode,
        public readonly string $teamName,
        public readonly string $ligaNombre,
        public readonly string $pais,
    ) {}
}
