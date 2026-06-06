<?php

namespace App\Futbol\Application\Favorito\Remove;

use App\Futbol\Domain\Repository\FutbolFavoritoRepositoryInterface;

final class RemoveFavoritoUseCase
{
    public function __construct(
        private readonly FutbolFavoritoRepositoryInterface $repository,
    ) {}

    public function execute(RemoveFavoritoCommand $command): bool
    {
        $favorito = $this->repository->findByUuid($command->uuid);
        if (!$favorito) {
            return false;
        }
        $this->repository->delete($favorito);
        return true;
    }
}
