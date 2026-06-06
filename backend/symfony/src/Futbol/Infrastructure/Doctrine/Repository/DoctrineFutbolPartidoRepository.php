<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolPartidoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolPartido;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolPartidoRepository implements FutbolPartidoRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?FutbolPartido
    {
        return $this->em->getRepository(FutbolPartido::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findByEspnEventId(string $eventId): ?FutbolPartido
    {
        return $this->em->getRepository(FutbolPartido::class)->findOneBy(['espnEventId' => $eventId]);
    }

    public function findByFecha(\DateTimeImmutable $fecha): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(FutbolPartido::class, 'p')
            ->where('p.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->orderBy('p.horaUtc', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendientesResultado(): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(FutbolPartido::class, 'p')
            ->where('p.estado = :estado')
            ->andWhere('p.golesLocal IS NULL')
            ->setParameter('estado', 'finalizado')
            ->getQuery()
            ->getResult();
    }

    public function save(FutbolPartido $partido): void
    {
        $this->em->persist($partido);
        $this->em->flush();
    }
}
