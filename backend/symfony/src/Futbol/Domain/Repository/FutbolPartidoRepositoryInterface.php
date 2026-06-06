<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolPartido;

interface FutbolPartidoRepositoryInterface
{
    public function findByUuid(string $uuid): ?FutbolPartido;
    public function findByEspnEventId(string $eventId): ?FutbolPartido;
    /** @return FutbolPartido[] */
    public function findByFecha(\DateTimeImmutable $fecha): array;
    /** @return FutbolPartido[] */
    public function findPendientesResultado(): array;
    public function save(FutbolPartido $partido): void;
}
