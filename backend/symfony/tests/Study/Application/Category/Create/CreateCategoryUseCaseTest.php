<?php

namespace App\Tests\Study\Application\Category\Create;

use App\Study\Application\Category\Create\CreateCategoryCommand;
use App\Study\Application\Category\Create\CreateCategoryUseCase;
use App\Study\Domain\Exception\CategoryAlreadyExistsException;
use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use PHPUnit\Framework\TestCase;

class CreateCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private CreateCategoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->useCase = new CreateCategoryUseCase($this->categoryRepository);
    }

    public function testCreateCategorySuccessfully(): void
    {
        $this->categoryRepository->method('findBySlug')->willReturn(null);
        $this->categoryRepository->expects($this->once())->method('save');

        $this->useCase->execute(new CreateCategoryCommand('php', 'PHP'));
    }

    public function testThrowsWhenCategoryAlreadyExists(): void
    {
        $existing = new Category('php', 'PHP');
        $this->categoryRepository->method('findBySlug')->willReturn($existing);

        $this->expectException(CategoryAlreadyExistsException::class);

        $this->useCase->execute(new CreateCategoryCommand('php', 'PHP'));
    }
}
