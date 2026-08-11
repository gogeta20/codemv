<?php

namespace App\Acciones\Application\Earnings\FetchLatestReport;

final readonly class FetchLatestEarningsReportCommand
{
    public function __construct(
        public string $symbol,
    ) {}
}
