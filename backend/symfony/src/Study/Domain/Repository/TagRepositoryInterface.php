<?php

namespace App\Study\Domain\Repository;

use App\Study\Infrastructure\Doctrine\Entity\Tag;

interface TagRepositoryInterface
{
    public function findBySlug(string $slug): ?Tag;

    /** Persists without flush — doctrine_transaction middleware handles the commit. */
    public function save(Tag $tag): void;
}
