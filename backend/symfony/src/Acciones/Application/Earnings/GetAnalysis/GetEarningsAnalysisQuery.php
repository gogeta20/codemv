<?php

namespace App\Acciones\Application\Earnings\GetAnalysis;

final readonly class GetEarningsAnalysisQuery
{
    public function __construct(
        public string $accionUuid,
    ) {}
}
