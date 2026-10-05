<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolCopaPartidoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolCopaPartido;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolCopaPartidoRepository implements FutbolCopaPartidoRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByCompetitionAndEventId(string $competitionCode, string $eventId): ?FutbolCopaPartido
    {
        return $this->em->getRepository(FutbolCopaPartido::class)->findOneBy([
            'competitionCode' => $competitionCode,
            'eventId' => $eventId,
        ]);
    }

    public function findByFecha(\DateTimeImmutable $fecha): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(FutbolCopaPartido::class, 'p')
            ->where('p.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->orderBy('p.horaUtc', 'ASC')
            ->addOrderBy('p.equipoLocal', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function removeByFecha(\DateTimeImmutable $fecha): void
    {
        $this->em->createQueryBuilder()
            ->delete(FutbolCopaPartido::class, 'p')
            ->where('p.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->getQuery()
            ->execute();
    }

    public function save(FutbolCopaPartido $partido, bool $flush = true): void
    {
        $this->em->persist($partido);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}
