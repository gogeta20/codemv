<?php

namespace App\Acciones\Application\Portafolio\GetPortafolio;

use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Domain\Repository\AccionPrecioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;

final class GetPortafolioUseCase
{
    public function __construct(
        private readonly PortafolioRepositoryInterface       $portafolioRepository,
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
        private readonly AccionPrecioRepositoryInterface     $precioRepository,
    ) {}

    public function execute(GetPortafolioQuery $query): array
    {
        $portafolio = $this->portafolioRepository->findByUuid($query->uuid);
        if ($portafolio === null) {
            throw new \RuntimeException("Portafolio '{$query->uuid}' not found.");
        }

        $entries = $this->portafolioAccionRepository->findByPortafolio($portafolio);

        $acciones = array_map(function (PortafolioAccion $e) {
            $data   = $e->toArray();
            $latest = $this->precioRepository->findByAccion($e->getAccion(), 1);
            if (!empty($latest)) {
                $p = $latest[0];
                $data['accion']['precio']        = (float) $p->getPriceClose();
                $data['accion']['change_pct']    = $p->getChangePct() !== null ? (float) $p->getChangePct() : null;
                $data['accion']['change_amount'] = $p->getChangeAmount() !== null ? (float) $p->getChangeAmount() : null;
                $data['accion']['price_date']    = $p->getDate()->format('Y-m-d');
            } else {
                $data['accion']['precio']        = null;
                $data['accion']['change_pct']    = null;
                $data['accion']['change_amount'] = null;
                $data['accion']['price_date']    = null;
            }
            return $data;
        }, $entries);

        return [
            ...$portafolio->toArray(),
            'acciones' => $acciones,
        ];
    }
}
