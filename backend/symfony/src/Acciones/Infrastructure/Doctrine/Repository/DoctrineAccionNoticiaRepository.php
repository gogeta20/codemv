<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionNoticiaRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionNoticia;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionNoticiaRepository implements AccionNoticiaRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByAccionAndDate(Accion $accion, \DateTimeImmutable $date): array
    {
        return $this->em->getRepository(AccionNoticia::class)
            ->createQueryBuilder('n')
            ->where('n.accion = :accion')
            ->andWhere('n.date = :date')
            ->setParameter('accion', $accion)
            ->setParameter('date', $date)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAccion(Accion $accion, int $limit = 50): array
    {
        return $this->em->getRepository(AccionNoticia::class)
            ->createQueryBuilder('n')
            ->where('n.accion = :accion')
            ->setParameter('accion', $accion)
            ->orderBy('n.date', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 20): array
    {
        return $this->em->getRepository(AccionNoticia::class)
            ->createQueryBuilder('n')
            ->leftJoin('n.accion', 'a')
            ->addSelect('a')
            ->orderBy('n.date', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(AccionNoticia $noticia): void
    {
        $this->em->persist($noticia);
        $this->em->flush();
    }
}
