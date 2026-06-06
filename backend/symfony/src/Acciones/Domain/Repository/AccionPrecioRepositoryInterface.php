<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionPrecio;

interface AccionPrecioRepositoryInterface
{
    public function findByAccionAndDate(Accion $accion, \DateTimeImmutable $date): ?AccionPrecio;
    /** @return AccionPrecio[] */
    public function findByAccion(Accion $accion, int $limit = 30): array;
    public function save(AccionPrecio $precio): void;
}
