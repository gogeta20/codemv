<?php

namespace App\Study\Application\Update;

use App\Study\Application\Shared\StudyCommandResult;
use App\Study\Domain\Exception\StudyNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateStudyCommandHandler
{
    public function __construct(
        private readonly UpdateStudyUseCase $useCase,
    ) {}

    public function __invoke(UpdateStudyCommand $command): StudyCommandResult
    {
        try {
            $study = $this->useCase->execute($command);
            return StudyCommandResult::ok($study->toArray());
        } catch (StudyNotFoundException) {
            return StudyCommandResult::notFound();
        } catch (\InvalidArgumentException $e) {
            return StudyCommandResult::badRequest($e->getMessage());
        }
    }
}
