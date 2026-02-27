<?php

namespace App\Tests\Study\Application\Tag\Create;

use App\Study\Application\Tag\Create\CreateTagCommand;
use App\Study\Application\Tag\Create\CreateTagUseCase;
use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Tag;
use PHPUnit\Framework\TestCase;

class CreateTagUseCaseTest extends TestCase
{
    private TagRepositoryInterface $tagRepository;
    private CreateTagUseCase $useCase;

    protected function setUp(): void
    {
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new CreateTagUseCase($this->tagRepository);
    }

    public function testCreateTagSuccessfully(): void
    {
        $this->tagRepository->method('findBySlug')->willReturn(null);
        $this->tagRepository->expects($this->once())->method('save');

        $this->useCase->execute(new CreateTagCommand('php', 'PHP'));
    }

    public function testIdempotentWhenTagExists(): void
    {
        $existing = new Tag('php', 'PHP');
        $this->tagRepository->method('findBySlug')->willReturn($existing);
        $this->tagRepository->expects($this->never())->method('save');

        $this->useCase->execute(new CreateTagCommand('php', 'PHP'));
    }

    public function testSlugIsNormalizedToLowercase(): void
    {
        $this->tagRepository
            ->method('findBySlug')
            ->with('php')
            ->willReturn(null);

        $this->tagRepository->expects($this->once())->method('save');

        $this->useCase->execute(new CreateTagCommand('  PHP  '));
    }

    public function testNameDefaultsToSlug(): void
    {
        $this->tagRepository->method('findBySlug')->willReturn(null);

        $this->tagRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Tag $tag): bool {
                return $tag->getSlug() === 'myslug' && $tag->getName() === 'myslug';
            }));

        $this->useCase->execute(new CreateTagCommand('myslug'));
    }
}
