<?php

namespace App\Acciones\Application\Portafolio\Delete;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeletePortafolioCommandHandler
{
    public function __construct(private readonly DeletePortafolioUseCase $useCase) {}

    public function __invoke(DeletePortafolioCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
