<?php

namespace App\Acciones\Application\Portafolio\RemoveAccion;

use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;

final class RemoveAccionUseCase
{
    public function __construct(
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(RemoveAccionCommand $command): void
    {
        $entry = $this->portafolioAccionRepository->findByUuid($command->entryUuid);
        if ($entry === null) {
            throw new \RuntimeException("Entry '{$command->entryUuid}' not found.");
        }
        $this->portafolioAccionRepository->delete($entry);
    }
}
