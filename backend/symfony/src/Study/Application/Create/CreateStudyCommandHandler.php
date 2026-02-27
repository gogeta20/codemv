<?php

namespace App\Study\Application\Create;

use App\Study\Application\Shared\StudyCommandResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateStudyCommandHandler
{
    public function __construct(
        private readonly CreateStudyUseCase $useCase,
    ) {}

    public function __invoke(CreateStudyCommand $command): StudyCommandResult
    {
        try {
            $study = $this->useCase->execute($command);
            return StudyCommandResult::created($study->toArray());
        } catch (\InvalidArgumentException $e) {
            return StudyCommandResult::badRequest($e->getMessage());
        }
    }
}
