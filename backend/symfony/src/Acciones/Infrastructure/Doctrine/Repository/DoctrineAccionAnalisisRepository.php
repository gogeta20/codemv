<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionAnalisisRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionAnalisisRepository implements AccionAnalisisRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(AccionAnalisis $analisis): void
    {
        $this->em->persist($analisis);
        $this->em->flush();
    }

    public function findLatestByAccionUuid(string $accionUuid): ?AccionAnalisis
    {
        return $this->em->getRepository(AccionAnalisis::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.accion', 'ac')
            ->where('ac.uuid = :uuid')
            ->setParameter('uuid', $accionUuid)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
