<?php

namespace App\Study\Infrastructure\Doctrine\Repository;

use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findBySlug(string $slug): ?Tag
    {
        return $this->em->getRepository(Tag::class)->findOneBy(['slug' => $slug]);
    }

    public function save(Tag $tag): void
    {
        $this->em->persist($tag);
        // Flush is handled by doctrine_transaction middleware on command.bus.
    }
}
