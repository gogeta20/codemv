<?php

namespace App\Futbol\Domain\Repository;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLigaGpm;

interface FutbolLigaGpmRepositoryInterface
{
    /** @return FutbolLigaGpm[] ordenadas por gpm DESC */
    public function findAllOrdenadas(): array;
}
