<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolLigaRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLiga;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolLigaRepository implements FutbolLigaRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?FutbolLiga
    {
        return $this->em->getRepository(FutbolLiga::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findByCodigoEspn(string $codigo): ?FutbolLiga
    {
        return $this->em->getRepository(FutbolLiga::class)->findOneBy(['codigoEspn' => $codigo]);
    }

    public function findActivas(): array
    {
        return $this->em->getRepository(FutbolLiga::class)->findBy(['activa' => true], ['pais' => 'ASC', 'division' => 'ASC']);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(FutbolLiga::class)->findBy([], ['pais' => 'ASC', 'division' => 'ASC']);
    }

    public function save(FutbolLiga $liga): void
    {
        $this->em->persist($liga);
        $this->em->flush();
    }
}
