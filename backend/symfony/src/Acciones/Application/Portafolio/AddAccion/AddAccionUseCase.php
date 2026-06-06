<?php

namespace App\Acciones\Application\Portafolio\AddAccion;

use App\Acciones\Domain\Exception\AccionNotFoundException;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;

final class AddAccionUseCase
{
    public function __construct(
        private readonly PortafolioRepositoryInterface       $portafolioRepository,
        private readonly AccionRepositoryInterface           $accionRepository,
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(AddAccionCommand $command): PortafolioAccion
    {
        $portafolio = $this->portafolioRepository->findByUuid($command->portafolioUuid);
        if ($portafolio === null) {
            throw new \RuntimeException("Portafolio '{$command->portafolioUuid}' not found.");
        }

        $accion = $this->accionRepository->findByUuid($command->accionUuid);
        if ($accion === null) {
            throw new AccionNotFoundException($command->accionUuid);
        }

        $entry = new PortafolioAccion(
            uuid:       \Ramsey\Uuid\Uuid::uuid4()->toString(),
            portafolio: $portafolio,
            accion:     $accion,
            status:     $command->status,
        );

        if ($command->precioReferencia !== null) {
            $entry->setPrecioReferencia((string) $command->precioReferencia);
        }
        if ($command->notas !== null) {
            $entry->setNotas($command->notas);
        }

        $this->portafolioAccionRepository->save($entry);

        return $entry;
    }
}
