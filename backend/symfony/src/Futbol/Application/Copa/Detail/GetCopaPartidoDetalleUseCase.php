<?php

namespace App\Futbol\Application\Copa\Detail;

use App\Futbol\Domain\Repository\FutbolCopaPartidoRepositoryInterface;

final class GetCopaPartidoDetalleUseCase
{
    public function __construct(private readonly FutbolCopaPartidoRepositoryInterface $repository) {}

    public function execute(GetCopaPartidoDetalleQuery $query): array
    {
        $partido = $this->repository->findByCompetitionAndEventId($query->competition, $query->eventId);

        return $partido?->toArray() ?? [];
    }
}
