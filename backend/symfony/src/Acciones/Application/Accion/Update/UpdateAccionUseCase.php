<?php

namespace App\Acciones\Application\Accion\Update;

use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Domain\Exception\AccionNotFoundException;

final class UpdateAccionUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
    ) {}

    public function execute(UpdateAccionCommand $command): Accion
    {
        $accion = $this->accionRepository->findByUuid($command->uuid);
        if ($accion === null) {
            throw new AccionNotFoundException($command->uuid);
        }

        if ($command->name !== null) {
            $accion->setName($command->name);
        }
        if ($command->isActive !== null) {
            $accion->setIsActive($command->isActive);
        }
        if ($command->alertThresholdPct !== null) {
            $accion->setAlertThresholdPct((string) $command->alertThresholdPct);
        }
        if ($command->exchange !== null) $accion->setExchange($command->exchange);
        if ($command->sector !== null) $accion->setSector($command->sector);
        if ($command->industry !== null) $accion->setIndustry($command->industry);

        $this->accionRepository->save($accion);

        return $accion;
    }
}
