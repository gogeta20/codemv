<?php

namespace App\Acciones\Application\Accion\Find;

use App\Acciones\Domain\Exception\AccionNotFoundException;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Domain\Repository\AccionNoticiaRepositoryInterface;
use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionNoticia;

final class FindAccionNoticiasUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface           $accionRepository,
        private readonly AccionNoticiaRepositoryInterface   $noticiaRepository,
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(FindAccionNoticiasQuery $query): array
    {
        $accion = $this->accionRepository->findByUuid($query->uuid);
        if ($accion === null) {
            throw new AccionNotFoundException($query->uuid);
        }

        $noticias = $this->noticiaRepository->findByAccion($accion);

        $portafolioMap = $this->portafolioAccionRepository->findFirstByAccionIds([$accion->getId()]);
        $pa            = $portafolioMap[$accion->getId()] ?? null;
        $portafolio    = $pa ? [
            'entry_uuid'    => $pa->getUuid(),
            'uuid'          => $pa->getPortafolio()->getUuid(),
            'nombre'        => $pa->getPortafolio()->getNombre(),
            'status'        => $pa->getStatus(),
        ] : null;

        return [
            'accion'     => $accion->toArray(),
            'portafolio' => $portafolio,
            'noticias'   => array_map(fn(AccionNoticia $n) => $n->toArray(), $noticias),
        ];
    }
}
