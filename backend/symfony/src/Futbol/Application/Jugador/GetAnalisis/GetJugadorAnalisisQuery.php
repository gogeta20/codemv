<?php

namespace App\Futbol\Application\Jugador\GetAnalisis;

final readonly class GetJugadorAnalisisQuery
{
    public function __construct(public readonly string $playerId) {}
}
