<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLiga;

interface FutbolLigaRepositoryInterface
{
    public function findByUuid(string $uuid): ?FutbolLiga;
    public function findByCodigoEspn(string $codigo): ?FutbolLiga;
    /** @return FutbolLiga[] */
    public function findActivas(): array;
    /** @return FutbolLiga[] */
    public function findAll(): array;
    public function save(FutbolLiga $liga): void;
}
