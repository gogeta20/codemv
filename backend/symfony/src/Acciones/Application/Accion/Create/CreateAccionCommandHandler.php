<?php

namespace App\Acciones\Application\Accion\Create;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateAccionCommandHandler
{
    public function __construct(
        private readonly CreateAccionUseCase $useCase,
    ) {}

    public function __invoke(CreateAccionCommand $command): Accion
    {
        return $this->useCase->execute($command);
    }
}
