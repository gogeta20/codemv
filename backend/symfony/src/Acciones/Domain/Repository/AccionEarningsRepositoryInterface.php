<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarnings;

interface AccionEarningsRepositoryInterface
{
    /** @param int[] $accionIds */
    public function findByAccionIds(array $accionIds): array;
    public function findByAccion(Accion $accion): ?AccionEarnings;
    /** @return AccionEarnings[] */
    public function findDueForReportFetch(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
    public function save(AccionEarnings $earnings): void;
}
