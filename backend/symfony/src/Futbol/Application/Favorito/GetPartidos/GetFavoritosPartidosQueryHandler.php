<?php

namespace App\Futbol\Application\Favorito\GetPartidos;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetFavoritosPartidosQueryHandler
{
    public function __construct(private readonly GetFavoritosPartidosUseCase $useCase) {}

    public function __invoke(GetFavoritosPartidosQuery $query): array
    {
        return $this->useCase->execute();
    }
}
