<?php

namespace App\Acciones\Application\Analisis\GetAnalisis;

use App\Acciones\Domain\Repository\AccionAnalisisRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetAnalisisQueryHandler
{
    public function __construct(
        private readonly AccionAnalisisRepositoryInterface $repository,
    ) {}

    public function __invoke(GetAnalisisQuery $query): ?AccionAnalisis
    {
        return $this->repository->findLatestByAccionUuid($query->accionUuid);
    }
}
