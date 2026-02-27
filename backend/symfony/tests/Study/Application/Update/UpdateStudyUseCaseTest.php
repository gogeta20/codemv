<?php

namespace App\Tests\Study\Application\Update;

use App\Study\Application\Update\UpdateStudyCommand;
use App\Study\Application\Update\UpdateStudyUseCase;
use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use PHPUnit\Framework\TestCase;

class UpdateStudyUseCaseTest extends TestCase
{
    private StudyRepositoryInterface $studyRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private TagRepositoryInterface $tagRepository;
    private UpdateStudyUseCase $useCase;

    protected function setUp(): void
    {
        $this->studyRepository = $this->createMock(StudyRepositoryInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);

        $this->useCase = new UpdateStudyUseCase(
            $this->studyRepository,
            $this->categoryRepository,
            $this->tagRepository,
        );
    }

    public function testUpdateStudyTitle(): void
    {
        $category = new Category('php', 'PHP');
        $study = new Study('uuid-1', 'Old Title', 'Content', $category);

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->studyRepository->expects($this->once())->method('save');

        $this->useCase->execute(new UpdateStudyCommand(
            uuid: 'uuid-1',
            title: 'New Title',
        ));

        $this->assertSame('New Title', $study->getTitle());
    }

    public function testThrowsWhenStudyNotFound(): void
    {
        $this->studyRepository->method('findByUuid')->willReturn(null);

        $this->expectException(StudyNotFoundException::class);

        $this->useCase->execute(new UpdateStudyCommand(uuid: 'nonexistent'));
    }

    public function testUpdateCategoryThrowsWhenCategoryNotFound(): void
    {
        $category = new Category('php', 'PHP');
        $study = new Study('uuid-1', 'Title', 'Content', $category);

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->categoryRepository->method('findBySlug')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        $this->useCase->execute(new UpdateStudyCommand(
            uuid: 'uuid-1',
            category: 'nonexistent',
        ));
    }

    public function testClearSummaryExplicitly(): void
    {
        $category = new Category('php', 'PHP');
        $study = new Study('uuid-1', 'Title', 'Content', $category, 'Old Summary');

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->studyRepository->expects($this->once())->method('save');

        $this->useCase->execute(new UpdateStudyCommand(
            uuid: 'uuid-1',
            setSummary: true,
            summary: null,
        ));

        $this->assertNull($study->getSummary());
    }
}
