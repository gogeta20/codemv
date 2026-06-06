<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolSeleccionDiariaRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolSeleccionDiaria;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolSeleccionDiariaRepository implements FutbolSeleccionDiariaRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?FutbolSeleccionDiaria
    {
        return $this->em->getRepository(FutbolSeleccionDiaria::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findByFecha(\DateTimeImmutable $fecha, ?string $tipo = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(FutbolSeleccionDiaria::class, 's')
            ->where('s.fecha = :fecha')
            ->setParameter('fecha', $fecha)
            ->orderBy('s.posicion', 'ASC');

        if ($tipo !== null) {
            $qb->andWhere('s.tipo = :tipo')->setParameter('tipo', $tipo);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(FutbolSeleccionDiaria $seleccion): void
    {
        $this->em->persist($seleccion);
        $this->em->flush();
    }

    public function deleteByFecha(\DateTimeImmutable $fecha, ?string $tipo = null): void
    {
        $qb = $this->em->createQueryBuilder()
            ->delete(FutbolSeleccionDiaria::class, 's')
            ->where('s.fecha = :fecha')
            ->setParameter('fecha', $fecha);

        if ($tipo !== null) {
            $qb->andWhere('s.tipo = :tipo')->setParameter('tipo', $tipo);
        }

        $qb->getQuery()->execute();
    }
}
