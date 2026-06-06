<?php

namespace App\Futbol\Application\Favorito\GetTeamsByLiga;

final class GetTeamsByLigaQuery
{
    public function __construct(public readonly string $ligaCode) {}
}
