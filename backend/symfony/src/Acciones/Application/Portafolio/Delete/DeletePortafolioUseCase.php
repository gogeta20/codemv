<?php

namespace App\Acciones\Application\Portafolio\Delete;

use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;

final class DeletePortafolioUseCase
{
    public function __construct(
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(DeletePortafolioCommand $command): void
    {
        $entry = $this->portafolioAccionRepository->findByUuid($command->uuid);
        if ($entry === null) {
            throw new \RuntimeException("Portafolio entry '{$command->uuid}' not found.");
        }
        $this->portafolioAccionRepository->delete($entry);
    }
}
