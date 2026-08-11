<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;

interface AccionEarningsReportRepositoryInterface
{
    /** @param int[] $accionIds */
    public function findLatestByAccionIds(array $accionIds): array;
    public function findLatestByAccion(Accion $accion): ?AccionEarningsReport;
    public function findByAccionAndAccessionNumber(Accion $accion, string $accessionNumber): ?AccionEarningsReport;
    public function save(AccionEarningsReport $report): void;
}
