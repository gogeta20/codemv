<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;
use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;

interface PortafolioAccionRepositoryInterface
{
    public function findByUuid(string $uuid): ?PortafolioAccion;
    /** @return PortafolioAccion[] */
    public function findByPortafolio(Portafolio $portafolio): array;
    /** Devuelve map accionId => PortafolioAccion (primer portafolio de cada acción) */
    public function findFirstByAccionIds(array $accionIds): array;
    public function save(PortafolioAccion $entry): void;
    public function delete(PortafolioAccion $entry): void;
}
