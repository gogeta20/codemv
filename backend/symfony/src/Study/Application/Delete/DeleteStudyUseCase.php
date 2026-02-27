<?php

namespace App\Study\Application\Delete;

use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;

final class DeleteStudyUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
    ) {}

    public function execute(DeleteStudyCommand $command): void
    {
        $study = $this->studyRepository->findByUuid($command->uuid);

        if ($study === null) {
            throw new StudyNotFoundException($command->uuid);
        }

        $this->studyRepository->delete($study);
    }
}
