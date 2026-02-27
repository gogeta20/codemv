<?php

namespace App\Study\Application\Category\Create;

use App\Study\Domain\Exception\CategoryAlreadyExistsException;
use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;

final class CreateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function execute(CreateCategoryCommand $command): void
    {
        $existing = $this->categoryRepository->findBySlug($command->slug);

        if ($existing !== null) {
            throw new CategoryAlreadyExistsException($command->slug);
        }

        $category = new Category($command->slug, $command->name);
        $this->categoryRepository->save($category);
    }
}
