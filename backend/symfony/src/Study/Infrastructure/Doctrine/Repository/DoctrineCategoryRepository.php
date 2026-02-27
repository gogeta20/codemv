<?php

namespace App\Study\Infrastructure\Doctrine\Repository;

use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findBySlug(string $slug): ?Category
    {
        return $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
    }
}
