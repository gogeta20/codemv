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

    public function save(AccionEarnings $earnings): void
    {
        $this->em->persist($earnings);
        $this->em->flush();
    }
}
