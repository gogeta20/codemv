<?php

namespace App\Study\Infrastructure\Doctrine\Repository;

use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineStudyRepository implements StudyRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function findByUuid(string $uuid): ?Study
    {
        return $this->em->getRepository(Study::class)->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Filters: category (slug), tags (comma-separated slugs), favorite (bool), status (string).
     *
     * @return Study[]
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->em->getRepository(Study::class)->createQueryBuilder('s')
            ->leftJoin('s.category', 'c')
            ->leftJoin('s.tags', 't')
            ->addSelect('c', 't');

        if (!empty($filters['category'])) {
            $qb->andWhere('c.slug = :category')->setParameter('category', $filters['category']);
        }

        if (!empty($filters['tags'])) {
            $tagSlugs = array_map('trim', explode(',', $filters['tags']));
            $qb->andWhere('t.slug IN (:tags)')->setParameter('tags', $tagSlugs);
        }

        if (!empty($filters['favorite'])) {
            $qb->andWhere('s.isFavorite = :fav')->setParameter('fav', true);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('s.status = :status')->setParameter('status', $filters['status']);
        }

        return $qb->orderBy('s.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function save(Study $study): void
    {
        $this->em->persist($study);
        // Flush here so the DB-assigned ID is available immediately (e.g. for toArray()).
        // doctrine_transaction middleware wraps this in a BEGIN/COMMIT, so atomicity is preserved.
        $this->em->flush();
    }

    public function delete(Study $study): void
    {
        $this->em->remove($study);
        $this->em->flush();
    }
}
