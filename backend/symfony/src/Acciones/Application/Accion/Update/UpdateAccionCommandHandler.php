<?php

namespace App\Acciones\Application\Accion\Update;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateAccionCommandHandler
{
    public function __construct(
        private readonly UpdateAccionUseCase $useCase,
    ) {}

    public function __invoke(UpdateAccionCommand $command): Accion
    {
        return $this->useCase->execute($command);
    }
}
