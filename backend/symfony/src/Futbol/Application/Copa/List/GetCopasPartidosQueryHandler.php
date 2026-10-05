<?php

namespace App\Futbol\Application\Copa\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCopasPartidosQueryHandler
{
    public function __construct(private readonly GetCopasPartidosUseCase $useCase) {}

    public function __invoke(GetCopasPartidosQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
