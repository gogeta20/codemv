<?php

namespace App\Study\Application\Find;

use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Study;

final class FindStudyUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
    ) {}

    public function execute(FindStudyQuery $query): Study
    {
        $study = $this->studyRepository->findByUuid($query->uuid);

        if ($study === null) {
            throw new StudyNotFoundException($query->uuid);
        }

        return $study;
    }
}
