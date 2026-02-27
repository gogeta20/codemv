<?php

namespace App\Tests\Study\Application\Delete;

use App\Study\Application\Delete\DeleteStudyCommand;
use App\Study\Application\Delete\DeleteStudyUseCase;
use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Category;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use PHPUnit\Framework\TestCase;

class DeleteStudyUseCaseTest extends TestCase
{
    private StudyRepositoryInterface $studyRepository;
    private DeleteStudyUseCase $useCase;

    protected function setUp(): void
    {
        $this->studyRepository = $this->createMock(StudyRepositoryInterface::class);
        $this->useCase = new DeleteStudyUseCase($this->studyRepository);
    }

    public function testDeleteStudySuccessfully(): void
    {
        $study = new Study('uuid-1', 'Title', 'Content', new Category('php', 'PHP'));

        $this->studyRepository->method('findByUuid')->willReturn($study);
        $this->studyRepository->expects($this->once())->method('delete')->with($study);

        $this->useCase->execute(new DeleteStudyCommand('uuid-1'));
    }

    public function testThrowsWhenStudyNotFound(): void
    {
        $this->studyRepository->method('findByUuid')->willReturn(null);

        $this->expectException(StudyNotFoundException::class);

        $this->useCase->execute(new DeleteStudyCommand('nonexistent'));
    }
}
