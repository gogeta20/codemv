<?php

namespace App\Acciones\Application\Earnings\FetchLatestReport;

use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class FetchLatestEarningsReportCommandHandler
{
    public function __construct(
        private readonly FetchLatestEarningsReportUseCase $useCase,
    ) {}

    public function __invoke(FetchLatestEarningsReportCommand $command): AccionEarningsReport
    {
        return $this->useCase->execute($command);
    }
}
