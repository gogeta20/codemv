<?php

namespace App\Futbol\Infrastructure\Doctrine\Repository;

use App\Futbol\Domain\Repository\FutbolFavoritoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolFavorito;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineFutbolFavoritoRepository implements FutbolFavoritoRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findAll(): array
    {
        return $this->em->getRepository(FutbolFavorito::class)
            ->createQueryBuilder('f')
            ->orderBy('f.teamName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUuid(string $uuid): ?FutbolFavorito
    {
        return $this->em->getRepository(FutbolFavorito::class)->findOneBy(['uuid' => $uuid]);
    }

    public function save(FutbolFavorito $favorito): void
    {
        $this->em->persist($favorito);
        $this->em->flush();
    }

    public function delete(FutbolFavorito $favorito): void
    {
        $this->em->remove($favorito);
        $this->em->flush();
    }
}
