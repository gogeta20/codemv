<?php

namespace App\Study\Application\Find;

use App\Study\Application\Shared\StudyReadModel;
use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;

final class FindStudyUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
    ) {}

    public function execute(FindStudyQuery $query): StudyReadModel
    {
        $study = $this->studyRepository->findByUuid($query->uuid);

        if ($study === null) {
            throw new StudyNotFoundException($query->uuid);
        }

        return StudyReadModel::fromEntity($study);
    }
}
