<?php

namespace App\Acciones\Application\Portafolio\UpdateAccion;

use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;

final class UpdateAccionUseCase
{
    public function __construct(
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(UpdateAccionCommand $command): PortafolioAccion
    {
        $entry = $this->portafolioAccionRepository->findByUuid($command->entryUuid);
        if ($entry === null) {
            throw new \RuntimeException("Entry '{$command->entryUuid}' not found.");
        }

        if ($command->status !== null) $entry->setStatus($command->status);
        if ($command->precioReferencia !== null) $entry->setPrecioReferencia((string) $command->precioReferencia);
        if ($command->notas !== null) $entry->setNotas($command->notas);

        $this->portafolioAccionRepository->save($entry);

        return $entry;
    }
}
