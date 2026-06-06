<?php

namespace App\Acciones\Application\Portafolio\List;

use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;

final class ListPortafolioUseCase
{
    public function __construct(
        private readonly PortafolioRepositoryInterface $portafolioRepository,
    ) {}

    public function execute(ListPortafolioQuery $query): array
    {
        $entries = $query->status
            ? $this->portafolioRepository->findByStatus($query->status)
            : $this->portafolioRepository->findAll();

        return array_map(fn(Portafolio $p) => $p->toArray(), $entries);
    }
}
