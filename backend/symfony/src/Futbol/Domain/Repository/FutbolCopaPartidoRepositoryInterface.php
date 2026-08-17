<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolCopaPartido;

interface FutbolCopaPartidoRepositoryInterface
{
    public function findByCompetitionAndEventId(string $competitionCode, string $eventId): ?FutbolCopaPartido;

    /** @return FutbolCopaPartido[] */
    public function findByFecha(\DateTimeImmutable $fecha): array;

    public function removeByFecha(\DateTimeImmutable $fecha): void;
    public function save(FutbolCopaPartido $partido, bool $flush = true): void;
    public function flush(): void;
}
