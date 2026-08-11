<?php

namespace App\Acciones\Application\Earnings\GetLatestReport;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetLatestEarningsReportQueryHandler
{
    public function __construct(
        private readonly GetLatestEarningsReportUseCase $useCase,
    ) {}

    public function __invoke(GetLatestEarningsReportQuery $query): array
    {
        return $this->useCase->execute($query->symbol);
    }
}
