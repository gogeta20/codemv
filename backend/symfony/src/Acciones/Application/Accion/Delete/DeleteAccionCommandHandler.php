<?php

namespace App\Acciones\Application\Accion\Delete;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteAccionCommandHandler
{
    public function __construct(
        private readonly DeleteAccionUseCase $useCase,
    ) {}

    public function __invoke(DeleteAccionCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
