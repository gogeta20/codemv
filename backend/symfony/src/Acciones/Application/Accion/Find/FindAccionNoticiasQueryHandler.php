<?php

namespace App\Acciones\Application\Accion\Find;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class FindAccionNoticiasQueryHandler
{
    public function __construct(
        private readonly FindAccionNoticiasUseCase $useCase,
    ) {}

    public function __invoke(FindAccionNoticiasQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
