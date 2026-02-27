<?php

namespace App\Study\Application\Category\List;

use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;

final class ListCategoriesUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function execute(): array
    {
        $categories = $this->categoryRepository->findAllSorted();

        return array_map(fn(Category $c) => $c->toArray(), $categories);
    }
}
