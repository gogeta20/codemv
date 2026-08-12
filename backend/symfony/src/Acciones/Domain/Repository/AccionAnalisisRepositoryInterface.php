<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;

interface AccionAnalisisRepositoryInterface
{
    public function save(AccionAnalisis $analisis): void;

    public function findLatestByAccionUuid(string $accionUuid): ?AccionAnalisis;

    /** @param int[] $accionIds @return array<int, AccionAnalisis> keyed by accion id */
    public function findLatestByAccionIds(array $accionIds): array;
}
