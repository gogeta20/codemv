<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionEarningsRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarnings;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionEarningsRepository implements AccionEarningsRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByAccionIds(array $accionIds): array
    {
        if (empty($accionIds)) {
            return [];
        }

        $rows = $this->em->createQuery(
            'SELECT e FROM App\Acciones\Infrastructure\Doctrine\Entity\AccionEarnings e
             WHERE e.accion IN (:ids)'
        )
        ->setParameter('ids', $accionIds)
        ->getResult();

        $map = [];
        foreach ($rows as $e) {
            $map[$e->getAccion()->getId()] = $e;
        }
        return $map;
    }

    public function findByAccion(Accion $accion): ?AccionEarnings
    {
        return $this->em->getRepository(AccionEarnings::class)->findOneBy(['accion' => $accion]);
    }

    public function findDueForReportFetch(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->em->getConnection()->fetchAllAssociative(
            'SELECT e.id
             FROM acciones_earnings e
             INNER JOIN acciones a ON a.id = e.accion_id
             WHERE e.earnings_date BETWEEN :from AND :to
               AND a.is_active = :active
             ORDER BY e.earnings_date ASC, a.symbol ASC',
            [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'active' => True,
            ]
        );

        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $entities = $this->em->getRepository(AccionEarnings::class)->findBy(['id' => $ids]);
        $map = [];
        foreach ($entities as $entity) {
            $map[$entity->getId()] = $entity;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }

    public function save(AccionEarnings $earnings): void
    {
        $this->em->persist($earnings);
        $this->em->flush();
    }
}
