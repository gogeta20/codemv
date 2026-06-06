<?php

namespace App\Acciones\Application\Accion\List;

use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Domain\Repository\AccionPrecioRepositoryInterface;
use App\Acciones\Domain\Repository\AccionEarningsRepositoryInterface;
use App\Acciones\Domain\Repository\PortafolioAccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;

final class ListAccionesUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface           $accionRepository,
        private readonly AccionPrecioRepositoryInterface     $precioRepository,
        private readonly AccionEarningsRepositoryInterface   $earningsRepository,
        private readonly PortafolioAccionRepositoryInterface $portafolioAccionRepository,
    ) {}

    public function execute(ListAccionesQuery $query): array
    {
        $acciones = $query->onlyActive === true
            ? $this->accionRepository->findActive()
            : $this->accionRepository->findAll();

        $ids           = array_map(fn(Accion $a) => $a->getId(), $acciones);
        $portafolioMap = $this->portafolioAccionRepository->findFirstByAccionIds($ids);
        $earningsMap   = $this->earningsRepository->findByAccionIds($ids);

        return array_map(function (Accion $a) use ($portafolioMap, $earningsMap) {
            $data   = $a->toArray();
            $latest = $this->precioRepository->findByAccion($a, 1);
            if (!empty($latest)) {
                $p = $latest[0];
                $data['precio']        = (float) $p->getPriceClose();
                $data['change_pct']    = $p->getChangePct() !== null ? (float) $p->getChangePct() : null;
                $data['change_amount'] = $p->getChangeAmount() !== null ? (float) $p->getChangeAmount() : null;
                $data['price_date']    = $p->getDate()->format('Y-m-d');
            } else {
                $data['precio']        = null;
                $data['change_pct']    = null;
                $data['change_amount'] = null;
                $data['price_date']    = null;
            }

            $pa = $portafolioMap[$a->getId()] ?? null;
            $data['portafolio'] = $pa ? [
                'uuid'   => $pa->getPortafolio()->getUuid(),
                'nombre' => $pa->getPortafolio()->getNombre(),
            ] : null;

            $e = $earningsMap[$a->getId()] ?? null;
            $data['earnings_date'] = $e ? $e->getEarningsDate()->format('Y-m-d') : null;
            $data['eps_estimate']  = $e && $e->getEpsEstimate() !== null ? (float) $e->getEpsEstimate() : null;

            return $data;
        }, $acciones);
    }
}
