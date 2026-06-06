<?php

namespace App\Futbol\Application\Favorito\List;

use App\Futbol\Domain\Repository\FutbolFavoritoRepositoryInterface;

final class ListFavoritosUseCase
{
    public function __construct(
        private readonly FutbolFavoritoRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return array_map(
            fn($f) => $f->toArray(),
            $this->repository->findAll()
        );
    }
}
