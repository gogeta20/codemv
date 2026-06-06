<?php

namespace App\Futbol\Application\Favorito\Remove;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RemoveFavoritoCommandHandler
{
    public function __construct(private readonly RemoveFavoritoUseCase $useCase) {}

    public function __invoke(RemoveFavoritoCommand $command): bool
    {
        return $this->useCase->execute($command);
    }
}
