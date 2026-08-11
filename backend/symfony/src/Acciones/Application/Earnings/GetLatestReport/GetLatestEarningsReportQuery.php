<?php

namespace App\Acciones\Application\Earnings\GetLatestReport;

final readonly class GetLatestEarningsReportQuery
{
    public function __construct(
        public string $symbol,
    ) {}
}
