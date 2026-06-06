<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;

interface AccionAnalisisRepositoryInterface
{
    public function save(AccionAnalisis $analisis): void;

    public function findLatestByAccionUuid(string $accionUuid): ?AccionAnalisis;
}
