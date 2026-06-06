<?php

namespace App\Futbol\Application\Partido\GetDetalle;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolPartido;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetPartidoDetalleQueryHandler
{
    public function __construct(
        private readonly GetPartidoDetalleUseCase $useCase,
    ) {}

    public function __invoke(GetPartidoDetalleQuery $query): ?FutbolPartido
    {
        return $this->useCase->execute($query);
    }
}
