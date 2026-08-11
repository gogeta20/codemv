<?php

namespace App\Futbol\Application\Liga\ListLigas;

use App\Futbol\Domain\Repository\FutbolLigaRepositoryInterface;

final class ListLigasUseCase
{
    public function __construct(
        private readonly FutbolLigaRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return array_map(
            fn($liga) => $liga->toArray(),
            $this->repository->findAll()
        );
    }
}
