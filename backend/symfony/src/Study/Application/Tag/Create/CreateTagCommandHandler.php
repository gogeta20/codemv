<?php

namespace App\Study\Application\Tag\Create;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateTagCommandHandler
{
    public function __construct(
        private readonly CreateTagUseCase $useCase,
    ) {}

    public function __invoke(CreateTagCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
