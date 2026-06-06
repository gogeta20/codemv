<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionRepository implements AccionRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?Accion
    {
        return $this->em->getRepository(Accion::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findBySymbol(string $symbol): ?Accion
    {
        return $this->em->getRepository(Accion::class)->findOneBy(['symbol' => strtoupper($symbol)]);
    }

    public function findActive(): array
    {
        return $this->em->getRepository(Accion::class)->findBy(['isActive' => true], ['symbol' => 'ASC']);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Accion::class)->findBy([], ['symbol' => 'ASC']);
    }

    public function save(Accion $accion): void
    {
        $this->em->persist($accion);
        $this->em->flush();
    }

    public function delete(Accion $accion): void
    {
        $this->em->remove($accion);
        $this->em->flush();
    }
}
