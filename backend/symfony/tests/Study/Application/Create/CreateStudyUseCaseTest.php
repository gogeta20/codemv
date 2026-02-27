<?php

namespace App\Tests\Study\Application\Create;

use App\Study\Application\Create\CreateStudyCommand;
use App\Study\Application\Create\CreateStudyUseCase;
use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use PHPUnit\Framework\TestCase;

class CreateStudyUseCaseTest extends TestCase
{
    private StudyRepositoryInterface $studyRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private TagRepositoryInterface $tagRepository;
    private CreateStudyUseCase $useCase;

    protected function setUp(): void
    {
        $this->studyRepository = $this->createMock(StudyRepositoryInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);

        $this->useCase = new CreateStudyUseCase(
            $this->studyRepository,
            $this->categoryRepository,
            $this->tagRepository,
        );
    }

    public function testCreateStudySuccessfully(): void
    {
        $category = new Category('php', 'PHP');

        $this->categoryRepository
            ->method('findBySlug')
            ->with('php')
            ->willReturn($category);

        $this->studyRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Study::class));

        $this->useCase->execute(new CreateStudyCommand(
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            title: 'Test Study',
            content: 'Content here',
            category: 'php',
        ));
    }

    public function testThrowsWhenCategoryNotFound(): void
    {
        $this->categoryRepository
            ->method('findBySlug')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Category 'unknown' not found.");

        $this->useCase->execute(new CreateStudyCommand(
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            title: 'Test',
            content: 'Content',
            category: 'unknown',
        ));
    }

    public function testCreateStudyWithTagsAndStatus(): void
    {
        $category = new Category('php', 'PHP');

        $this->categoryRepository->method('findBySlug')->willReturn($category);
        $this->tagRepository->method('findBySlug')->willReturn(null);
        $this->tagRepository->expects($this->exactly(2))->method('save');
        $this->studyRepository->expects($this->once())->method('save');

        $this->useCase->execute(new CreateStudyCommand(
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            title: 'Test',
            content: 'Content',
            category: 'php',
            tags: ['tag1', 'tag2'],
            status: 'published',
        ));
    }
}
