<?php

namespace App\Futbol\Application\Favorito\Add;

use App\Futbol\Domain\Repository\FutbolFavoritoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolFavorito;

final class AddFavoritoUseCase
{
    public function __construct(
        private readonly FutbolFavoritoRepositoryInterface $repository,
    ) {}

    public function execute(AddFavoritoCommand $command): FutbolFavorito
    {
        $favorito = new FutbolFavorito(
            uuid:         \Symfony\Component\Uid\Uuid::v4()->toRfc4122(),
            espnTeamId:   $command->espnTeamId,
            espnLigaCode: $command->espnLigaCode,
            teamName:     $command->teamName,
            ligaNombre:   $command->ligaNombre,
            pais:         $command->pais,
        );

        $this->repository->save($favorito);

        return $favorito;
    }
}
