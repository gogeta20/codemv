<?php

namespace App\Acciones\Application\Portafolio\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListPortafolioQueryHandler
{
    public function __construct(private readonly ListPortafolioUseCase $useCase) {}

    public function __invoke(ListPortafolioQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
