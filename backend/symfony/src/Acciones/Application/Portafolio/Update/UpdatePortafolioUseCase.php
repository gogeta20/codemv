<?php

namespace App\Acciones\Application\Portafolio\Update;

use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;

final class UpdatePortafolioUseCase
{
    public function __construct(
        private readonly PortafolioRepositoryInterface $portafolioRepository,
    ) {}

    public function execute(UpdatePortafolioCommand $command): Portafolio
    {
        $portafolio = $this->portafolioRepository->findByUuid($command->uuid);
        if ($portafolio === null) {
            throw new \RuntimeException("Portafolio '{$command->uuid}' not found.");
        }

        if ($command->nombre !== null) $portafolio->setNombre($command->nombre);
        if ($command->descripcion !== null) $portafolio->setDescripcion($command->descripcion);
        if ($command->isDefault === true) {
            $this->portafolioRepository->clearDefault();
            $portafolio->setIsDefault(true);
        }

        $this->portafolioRepository->save($portafolio);

        return $portafolio;
    }
}
