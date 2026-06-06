<?php

namespace App\Acciones\Application\Analisis\AnalizarAccion;

final readonly class AnalizarAccionCommand
{
    public function __construct(
        public string  $accionUuid,
        public string  $incomeContent,
        public string  $balanceContent,
        public string  $cashflowContent,
        public ?float  $priceAtAnalysis = null,
    ) {}
}
