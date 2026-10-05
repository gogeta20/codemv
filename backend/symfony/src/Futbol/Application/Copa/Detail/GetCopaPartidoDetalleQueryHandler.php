<?php

namespace App\Futbol\Application\Copa\Detail;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCopaPartidoDetalleQueryHandler
{
    public function __construct(private readonly GetCopaPartidoDetalleUseCase $useCase) {}

    public function __invoke(GetCopaPartidoDetalleQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
