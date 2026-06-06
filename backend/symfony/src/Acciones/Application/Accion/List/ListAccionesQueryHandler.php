<?php

namespace App\Acciones\Application\Accion\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListAccionesQueryHandler
{
    public function __construct(
        private readonly ListAccionesUseCase $useCase,
    ) {}

    public function __invoke(ListAccionesQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
