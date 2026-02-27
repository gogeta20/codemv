<?php

namespace App\Study\Application\Create;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateStudyCommandHandler
{
    public function __construct(
        private readonly CreateStudyUseCase $useCase,
    ) {}

    public function __invoke(CreateStudyCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
