<?php

namespace App\Study\Application\Update;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateStudyCommandHandler
{
    public function __construct(
        private readonly UpdateStudyUseCase $useCase,
    ) {}

    public function __invoke(UpdateStudyCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
