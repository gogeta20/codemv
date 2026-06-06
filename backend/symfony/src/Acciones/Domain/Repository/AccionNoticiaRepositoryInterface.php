<?php

namespace App\Acciones\Domain\Repository;

use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionNoticia;

interface AccionNoticiaRepositoryInterface
{
    /** @return AccionNoticia[] */
    public function findByAccionAndDate(Accion $accion, \DateTimeImmutable $date): array;
    /** @return AccionNoticia[] */
    public function findByAccion(Accion $accion, int $limit = 50): array;
    /** @return AccionNoticia[] */
    public function findRecent(int $limit = 20): array;
    public function save(AccionNoticia $noticia): void;
}
