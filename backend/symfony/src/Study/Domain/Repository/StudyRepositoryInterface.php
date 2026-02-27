<?php

namespace App\Study\Domain\Repository;

use App\Study\Infrastructure\Doctrine\Entity\Study;

interface StudyRepositoryInterface
{
    public function findByUuid(string $uuid): ?Study;

    /** @return Study[] */
    public function findWithFilters(array $filters): array;

    public function save(Study $study): void;

    public function delete(Study $study): void;
}
