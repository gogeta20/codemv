<?php

namespace App\Futbol\Application\Liga\ToggleLigaActiva;

use App\Futbol\Domain\Repository\FutbolLigaRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLiga;
use Symfony\Component\Uid\Uuid;

final class ToggleLigaActivaUseCase
{
    public function __construct(
        private readonly FutbolLigaRepositoryInterface $repository,
    ) {}

    public function execute(ToggleLigaActivaCommand $command): FutbolLiga
    {
        $liga = $this->repository->findByCodigoEspn($command->codigoEspn);

        if ($liga === null) {
            $liga = new FutbolLiga(
                uuid:       Uuid::v4()->toRfc4122(),
                nombre:     $command->nombre,
                codigoEspn: $command->codigoEspn,
                pais:       $command->pais,
            );
        }

        $liga->setActiva($command->activa);
        $this->repository->save($liga);

        return $liga;
    }
}
