<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolFavorito;

interface FutbolFavoritoRepositoryInterface
{
    /** @return FutbolFavorito[] */
    public function findAll(): array;
    public function findByUuid(string $uuid): ?FutbolFavorito;
    public function save(FutbolFavorito $favorito): void;
    public function delete(FutbolFavorito $favorito): void;
}
