<?php

namespace App\Acciones\Application\Portafolio\GetPortafolio;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPortafolioQueryHandler
{
    public function __construct(private readonly GetPortafolioUseCase $useCase) {}

    public function __invoke(GetPortafolioQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
