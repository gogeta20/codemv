<?php

namespace App\Acciones\Application\Analisis\AnalizarAccion;

use App\Acciones\Domain\Repository\AccionAnalisisRepositoryInterface;
use App\Acciones\Domain\Repository\AccionRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;
use App\Acciones\Infrastructure\Service\CsvParserService;
use App\Acciones\Infrastructure\Service\FairValueService;
use App\Acciones\Infrastructure\Service\MetricExtractorService;
use App\Acciones\Infrastructure\Service\RetratoPainter;
use App\Acciones\Infrastructure\Service\ScoringService;
use Ramsey\Uuid\Uuid;

final class AnalizarAccionUseCase
{
    public function __construct(
        private readonly AccionRepositoryInterface         $accionRepository,
        private readonly AccionAnalisisRepositoryInterface $analisisRepository,
        private readonly CsvParserService                  $csvParser,
        private readonly MetricExtractorService            $extractor,
        private readonly ScoringService                    $scorer,
        private readonly RetratoPainter                    $painter,
        private readonly FairValueService                  $fairValueService,
    ) {}

    public function execute(AnalizarAccionCommand $command): AccionAnalisis
    {
        $accion = $this->accionRepository->findByUuid($command->accionUuid);
        if ($accion === null) {
            throw new \InvalidArgumentException("Acción no encontrada: {$command->accionUuid}");
        }

        $rawIncome   = $this->csvParser->parseIncome($command->incomeContent);
        $rawBalance  = $this->csvParser->parseBalance($command->balanceContent);
        $rawCashflow = $this->csvParser->parseCashflow($command->cashflowContent);

        $metrics     = $this->extractor->extract($rawIncome, $rawBalance, $rawCashflow);
        $scoreResult = $this->scorer->score($metrics);
        $portrait    = $this->painter->paint($metrics, $scoreResult);

        $analisis = new AccionAnalisis(
            uuid:        Uuid::uuid4()->toString(),
            accion:      $accion,
            rawIncome:   $rawIncome,
            rawBalance:  $rawBalance,
            rawCashflow: $rawCashflow,
            metrics:     $metrics,
            score:       $scoreResult['score'],
            scoreDetail: $scoreResult['score_detail'],
            portrait:    $portrait,
        );

        $analisis->setStage($scoreResult['stage']);
        $analisis->setSpeculativeType($scoreResult['speculative_type']);
        $analisis->setInvestmentType($scoreResult['investment_type']);

        if ($command->priceAtAnalysis !== null) {
            $analisis->setPriceAtAnalysis((string) $command->priceAtAnalysis);
        }

        $fairValue = $this->fairValueService->compute($metrics, $scoreResult);
        if ($fairValue['value'] !== null) {
            $analisis->setFairValue((string) $fairValue['value']);
        }
        $analisis->setFairValueMethod($fairValue['method']);
        $analisis->setFairValueDetail($fairValue);

        $this->analisisRepository->save($analisis);

        return $analisis;
    }
}
