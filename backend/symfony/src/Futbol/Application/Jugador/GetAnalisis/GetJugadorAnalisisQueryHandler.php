<?php

namespace App\Futbol\Application\Jugador\GetAnalisis;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetJugadorAnalisisQueryHandler
{
    public function __construct(private readonly GetJugadorAnalisisUseCase $useCase) {}

    public function __invoke(GetJugadorAnalisisQuery $query): array
    {
        return $this->useCase->execute($query->playerId);
    }
}
