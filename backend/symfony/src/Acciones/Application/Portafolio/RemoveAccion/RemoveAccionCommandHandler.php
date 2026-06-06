<?php

namespace App\Acciones\Application\Portafolio\RemoveAccion;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RemoveAccionCommandHandler
{
    public function __construct(private readonly RemoveAccionUseCase $useCase) {}

    public function __invoke(RemoveAccionCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
