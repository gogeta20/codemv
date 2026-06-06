<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;
use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePortafolioAccionRepository implements PortafolioAccionRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?PortafolioAccion
    {
        return $this->em->getRepository(PortafolioAccion::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findByPortafolio(Portafolio $portafolio): array
    {
        return $this->em->getRepository(PortafolioAccion::class)
            ->createQueryBuilder('pa')
            ->leftJoin('pa.accion', 'a')
            ->addSelect('a')
            ->where('pa.portafolio = :portafolio')
            ->setParameter('portafolio', $portafolio)
            ->orderBy('pa.status', 'ASC')
            ->addOrderBy('a.symbol', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFirstByAccionIds(array $accionIds): array
    {
        if (empty($accionIds)) return [];

        $rows = $this->em->getRepository(PortafolioAccion::class)
            ->createQueryBuilder('pa')
            ->leftJoin('pa.portafolio', 'p')
            ->leftJoin('pa.accion', 'a')
            ->addSelect('p', 'a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $accionIds)
            ->orderBy('pa.id', 'ASC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $accionId = $row->getAccion()->getId();
            if (!isset($map[$accionId])) {
                $map[$accionId] = $row;
            }
        }
        return $map;
    }

    public function save(PortafolioAccion $entry): void
    {
        $this->em->persist($entry);
        $this->em->flush();
    }

    public function delete(PortafolioAccion $entry): void
    {
        $this->em->remove($entry);
        $this->em->flush();
    }
}
