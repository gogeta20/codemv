<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;

interface AccionRepositoryInterface
{
    public function findByUuid(string $uuid): ?Accion;
    public function findBySymbol(string $symbol): ?Accion;
    /** @return Accion[] */
    public function findActive(): array;
    /** @return Accion[] */
    public function findAll(): array;
    public function save(Accion $accion): void;
    public function delete(Accion $accion): void;
}
