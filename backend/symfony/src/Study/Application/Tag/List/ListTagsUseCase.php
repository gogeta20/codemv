<?php

namespace App\Study\Application\Tag\List;

use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final class ListTagsUseCase
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(): array
    {
        $tags = $this->tagRepository->findAllSorted();

        return array_map(fn(Tag $t) => $t->toArray(), $tags);
    }
}
