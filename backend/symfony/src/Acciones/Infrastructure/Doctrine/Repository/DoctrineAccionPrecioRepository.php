<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionPrecioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionPrecio;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionPrecioRepository implements AccionPrecioRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByAccionAndDate(Accion $accion, \DateTimeImmutable $date): ?AccionPrecio
    {
        return $this->em->getRepository(AccionPrecio::class)->findOneBy([
            'accion' => $accion,
            'date'   => $date,
        ]);
    }

    public function findByAccion(Accion $accion, int $limit = 30): array
    {
        return $this->em->getRepository(AccionPrecio::class)
            ->createQueryBuilder('p')
            ->where('p.accion = :accion')
            ->setParameter('accion', $accion)
            ->orderBy('p.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(AccionPrecio $precio): void
    {
        $this->em->persist($precio);
        $this->em->flush();
    }
}
