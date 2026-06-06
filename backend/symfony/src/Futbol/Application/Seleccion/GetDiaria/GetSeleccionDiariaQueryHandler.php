<?php

namespace App\Futbol\Application\Seleccion\GetDiaria;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetSeleccionDiariaQueryHandler
{
    public function __construct(
        private readonly GetSeleccionDiariaUseCase $useCase,
    ) {}

    public function __invoke(GetSeleccionDiariaQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
