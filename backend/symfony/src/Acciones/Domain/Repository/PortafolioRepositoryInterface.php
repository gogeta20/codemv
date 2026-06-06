<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;

interface PortafolioRepositoryInterface
{
    public function findByUuid(string $uuid): ?Portafolio;
    public function findDefault(): ?Portafolio;
    /** @return Portafolio[] */
    public function findAll(): array;
    public function clearDefault(): void;
    public function save(Portafolio $portafolio): void;
    public function delete(Portafolio $portafolio): void;
}
