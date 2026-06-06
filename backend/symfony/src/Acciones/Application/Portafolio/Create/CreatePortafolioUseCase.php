<?php

namespace App\Acciones\Application\Portafolio\Create;

use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;

final class CreatePortafolioUseCase
{
    public function __construct(
        private readonly PortafolioRepositoryInterface $portafolioRepository,
    ) {}

    public function execute(CreatePortafolioCommand $command): Portafolio
    {
        if ($command->isDefault) {
            $this->portafolioRepository->clearDefault();
        }

        $portafolio = new Portafolio(
            uuid:        \Ramsey\Uuid\Uuid::uuid4()->toString(),
            nombre:      $command->nombre,
            descripcion: $command->descripcion,
            isDefault:   $command->isDefault,
        );

        $this->portafolioRepository->save($portafolio);

        return $portafolio;
    }
}
