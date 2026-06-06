<?php

namespace App\Futbol\Application\Partido\GetDetalle;

use App\Futbol\Domain\Repository\FutbolPartidoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolPartido;

final class GetPartidoDetalleUseCase
{
    public function __construct(
        private readonly FutbolPartidoRepositoryInterface $partidoRepository,
    ) {}

    public function execute(GetPartidoDetalleQuery $query): ?FutbolPartido
    {
        return $this->partidoRepository->findByUuid($query->uuid);
    }
}
