<?php

namespace App\Futbol\Application\Copa\List;

use App\Futbol\Domain\Repository\FutbolCopaPartidoRepositoryInterface;

final class GetCopasPartidosUseCase
{
    public function __construct(private readonly FutbolCopaPartidoRepositoryInterface $repository) {}

    public function execute(GetCopasPartidosQuery $query): array
    {
        $fecha = $query->fecha ?? new \DateTimeImmutable('today');

        return array_map(
            static fn($partido) => $partido->toArray(),
            $this->repository->findByFecha($fecha),
        );
    }
}
