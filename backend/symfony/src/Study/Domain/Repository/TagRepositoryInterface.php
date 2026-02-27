<?php

namespace App\Study\Domain\Repository;

use App\Study\Infrastructure\Doctrine\Entity\Tag;

interface TagRepositoryInterface
{
    public function findBySlug(string $slug): ?Tag;

    /** @return Tag[] */
    public function findAllSorted(): array;

    public function save(Tag $tag): void;
}
