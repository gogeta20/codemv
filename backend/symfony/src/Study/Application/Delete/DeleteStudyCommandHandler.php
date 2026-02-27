<?php

namespace App\Study\Application\Delete;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteStudyCommandHandler
{
    public function __construct(
        private readonly DeleteStudyUseCase $useCase,
    ) {}

    public function __invoke(DeleteStudyCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
