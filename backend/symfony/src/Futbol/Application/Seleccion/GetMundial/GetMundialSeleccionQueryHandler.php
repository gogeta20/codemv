<?php

namespace App\Futbol\Application\Seleccion\GetMundial;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMundialSeleccionQueryHandler
{
    public function __construct(
        private readonly GetMundialSeleccionUseCase $useCase,
    ) {}

    public function __invoke(GetMundialSeleccionQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
