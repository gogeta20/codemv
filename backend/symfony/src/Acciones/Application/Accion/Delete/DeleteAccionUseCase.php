<?php

namespace App\Acciones\Application\Accion\Delete;

use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Domain\Exception\AccionNotFoundException;

final class DeleteAccionUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
    ) {}

    public function execute(DeleteAccionCommand $command): void
    {
        $accion = $this->accionRepository->findByUuid($command->uuid);
        if ($accion === null) {
            throw new AccionNotFoundException($command->uuid);
        }

        $this->accionRepository->delete($accion);
    }
}
