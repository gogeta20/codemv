<?php

namespace App\Study\Application\ToggleFavorite;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ToggleFavoriteCommandHandler
{
    public function __construct(
        private readonly ToggleFavoriteUseCase $useCase,
    ) {}

    public function __invoke(ToggleFavoriteCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
