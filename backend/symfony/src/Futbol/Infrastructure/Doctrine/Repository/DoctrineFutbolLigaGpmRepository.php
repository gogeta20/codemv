<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolLigaGpmRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLigaGpm;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineFutbolLigaGpmRepository implements FutbolLigaGpmRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findAllOrdenadas(): array
    {
        return $this->em
            ->getRepository(FutbolLigaGpm::class)
            ->findBy([], ['gpm' => 'DESC']);
    }
}
