<?php

namespace App\Futbol\Application\Favorito\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListFavoritosQueryHandler
{
    public function __construct(private readonly ListFavoritosUseCase $useCase) {}

    public function __invoke(ListFavoritosQuery $query): array
    {
        return $this->useCase->execute();
    }
}
