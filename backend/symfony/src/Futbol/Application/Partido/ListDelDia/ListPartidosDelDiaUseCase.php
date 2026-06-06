<?php

namespace App\Futbol\Application\Partido\ListDelDia;

use App\Futbol\Domain\Repository\FutbolPartidoRepositoryInterface;

final class ListPartidosDelDiaUseCase
{
    public function __construct(
        private readonly FutbolPartidoRepositoryInterface $partidoRepository,
    ) {}

    public function execute(ListPartidosDelDiaQuery $query): array
    {
        $fecha    = $query->fecha ?? new \DateTimeImmutable('today');
        $partidos = $this->partidoRepository->findByFecha($fecha);

        return array_map(fn($p) => $p->toArray(), $partidos);
    }
}
