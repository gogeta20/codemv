<?php

namespace App\Acciones\Application\Accion\Create;

use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;

final class CreateAccionUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface $accionRepository,
    ) {}

    public function execute(CreateAccionCommand $command): Accion
    {
        $existing = $this->accionRepository->findBySymbol($command->symbol);
        if ($existing !== null) {
            throw new \InvalidArgumentException("El símbolo {$command->symbol} ya existe.");
        }

        $accion = new Accion(
            uuid:   \Ramsey\Uuid\Uuid::uuid4()->toString(),
            symbol: $command->symbol,
            name:   $command->name,
            type:   $command->type,
            source: $command->source,
        );
        $accion->setAlertThresholdPct((string) $command->alertThresholdPct);
        if ($command->exchange !== null) $accion->setExchange($command->exchange);
        if ($command->sector !== null) $accion->setSector($command->sector);
        if ($command->industry !== null) $accion->setIndustry($command->industry);

        $this->accionRepository->save($accion);

        return $accion;
    }
}
