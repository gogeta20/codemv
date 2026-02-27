<?php

namespace App\Study\Application\ToggleFavorite;

use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Study;

final class ToggleFavoriteUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
    ) {}

    public function execute(ToggleFavoriteCommand $command): Study
    {
        $study = $this->studyRepository->findByUuid($command->uuid);

        if ($study === null) {
            throw new StudyNotFoundException($command->uuid);
        }

        $study->toggleFavorite();
        $this->studyRepository->save($study);

        return $study;
    }
}
