<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolAnalisisRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolAnalisis;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolPartido;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolAnalisisRepository implements FutbolAnalisisRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?FutbolAnalisis
    {
        return $this->em->getRepository(FutbolAnalisis::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findByPartidoUuid(string $partidoUuid): ?FutbolAnalisis
    {
        $partido = $this->em->getRepository(FutbolPartido::class)->findOneBy(['uuid' => $partidoUuid]);
        if (!$partido) {
            return null;
        }

        return $this->em->getRepository(FutbolAnalisis::class)->findOneBy(['partido' => $partido]);
    }

    public function findPendientesVerificacion(): array
    {
        return $this->em->createQueryBuilder()
            ->select('a')
            ->from(FutbolAnalisis::class, 'a')
            ->join('a.partido', 'p')
            ->where('p.estado = :estado')
            ->andWhere('a.aciertoGanador IS NULL')
            ->setParameter('estado', 'finalizado')
            ->getQuery()
            ->getResult();
    }

    public function save(FutbolAnalisis $analisis): void
    {
        $this->em->persist($analisis);
        $this->em->flush();
    }
}
