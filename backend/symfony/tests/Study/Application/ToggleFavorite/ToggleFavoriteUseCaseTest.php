<?php

namespace App\Tests\Study\Application\ToggleFavorite;

use App\Study\Application\ToggleFavorite\ToggleFavoriteCommand;
use App\Study\Application\ToggleFavorite\ToggleFavoriteUseCase;
use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use PHPUnit\Framework\TestCase;

class ToggleFavoriteUseCaseTest extends TestCase
{
    private StudyRepositoryInterface $studyRepository;
    private ToggleFavoriteUseCase $useCase;

    protected function setUp(): void
    {
        $this->studyRepository = $this->createMock(StudyRepositoryInterface::class);
        $this->useCase = new ToggleFavoriteUseCase($this->studyRepository);
    }

    public function testToggleFavorite(): void
    {
        $study = new Study('uuid-1', 'Title', 'Content', new Category('php', 'PHP'));
        $this->assertFalse($study->isFavorite());

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->studyRepository->expects($this->once())->method('save');

        $this->useCase->execute(new ToggleFavoriteCommand('uuid-1'));

        $this->assertTrue($study->isFavorite());
    }

    public function testToggleFavoriteBack(): void
    {
        $study = new Study('uuid-1', 'Title', 'Content', new Category('php', 'PHP'));
        $study->toggleFavorite(); // now true

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->studyRepository->expects($this->once())->method('save');

        $this->useCase->execute(new ToggleFavoriteCommand('uuid-1'));

        $this->assertFalse($study->isFavorite());
    }

    public function testThrowsWhenStudyNotFound(): void
    {
        $this->studyRepository->method('findByUuid')->willReturn(null);

        $this->expectException(StudyNotFoundException::class);

        $this->useCase->execute(new ToggleFavoriteCommand('nonexistent'));
    }
}
