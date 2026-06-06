<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolAnalisis;

interface FutbolAnalisisRepositoryInterface
{
    public function findByUuid(string $uuid): ?FutbolAnalisis;
    public function findByPartidoUuid(string $partidoUuid): ?FutbolAnalisis;
    /** @return FutbolAnalisis[] */
    public function findPendientesVerificacion(): array;
    public function save(FutbolAnalisis $analisis): void;
}
