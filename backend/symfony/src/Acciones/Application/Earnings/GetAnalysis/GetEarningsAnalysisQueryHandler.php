<?php

namespace App\Acciones\Application\Earnings\GetAnalysis;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetEarningsAnalysisQueryHandler
{
    public function __construct(
        private readonly GetEarningsAnalysisUseCase $useCase,
    ) {}

    public function __invoke(GetEarningsAnalysisQuery $query): ?array
    {
        return $this->useCase->execute($query->accionUuid);
    }
}
