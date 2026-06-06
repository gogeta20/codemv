<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolSeleccionDiaria;

interface FutbolSeleccionDiariaRepositoryInterface
{
    public function findByUuid(string $uuid): ?FutbolSeleccionDiaria;
    /** @return FutbolSeleccionDiaria[] */
    public function findByFecha(\DateTimeImmutable $fecha, ?string $tipo = null): array;
    public function save(FutbolSeleccionDiaria $seleccion): void;
    public function deleteByFecha(\DateTimeImmutable $fecha, ?string $tipo = null): void;
}
