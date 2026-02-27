<?php

namespace App\Study\Domain\Repository;

use App\Study\Infrastructure\Doctrine\Entity\Category;

interface CategoryRepositoryInterface
{
    public function findBySlug(string $slug): ?Category;
}
