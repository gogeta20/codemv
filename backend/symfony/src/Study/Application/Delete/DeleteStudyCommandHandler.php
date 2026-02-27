<?php

namespace App\Study\Application\Delete;

use App\Study\Application\Shared\StudyCommandResult;
use App\Study\Domain\Exception\StudyNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteStudyCommandHandler
{
    public function __construct(
        private readonly DeleteStudyUseCase $useCase,
    ) {}

    public function __invoke(DeleteStudyCommand $command): StudyCommandResult
    {
        try {
            $this->useCase->execute($command);
            return StudyCommandResult::deleted();
        } catch (StudyNotFoundException) {
            return StudyCommandResult::notFound();
        }
    }
}
