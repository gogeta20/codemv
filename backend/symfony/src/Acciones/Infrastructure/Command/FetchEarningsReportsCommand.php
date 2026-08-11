<?php

namespace App\Acciones\Infrastructure\Command;

use App\Acciones\Application\Earnings\FetchLatestReport\FetchLatestEarningsReportCommand as FetchLatestEarningsReportMessage;
use App\Acciones\Domain\Repository\AccionEarningsRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(
    name: 'app:acciones:fetch-earnings-reports',
    description: 'Busca y guarda reportes de earnings para acciones con earnings_date cercano o vencido.',
)]
final class FetchEarningsReportsCommand extends Command
{
    public function __construct(
        private readonly AccionEarningsRepositoryInterface $earningsRepository,
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('symbol', null, InputOption::VALUE_OPTIONAL, 'Fuerza la ejecución para un símbolo concreto')
            ->addOption('days-back', null, InputOption::VALUE_OPTIONAL, 'Cuántos días hacia atrás considerar como vencidos', '7')
            ->addOption('days-forward', null, InputOption::VALUE_OPTIONAL, 'Cuántos días hacia adelante considerar como cercanos', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $symbol = strtoupper(trim((string) $input->getOption('symbol')));

        if ($symbol !== '') {
            return $this->fetchSingleSymbol($symbol, $io);
        }

        $daysBack = max(0, (int) $input->getOption('days-back'));
        $daysForward = max(0, (int) $input->getOption('days-forward'));
        $today = new \DateTimeImmutable('today');
        $from = $today->modify(sprintf('-%d days', $daysBack));
        $to = $today->modify(sprintf('+%d days', $daysForward));

        $earningsRows = $this->earningsRepository->findDueForReportFetch($from, $to);
        if ($earningsRows === []) {
            $io->success(sprintf(
                'No hay acciones con earnings_date entre %s y %s.',
                $from->format('Y-m-d'),
                $to->format('Y-m-d')
            ));

            return Command::SUCCESS;
        }

        $io->section(sprintf(
            'Buscando reportes para %d acción(es) con earnings_date entre %s y %s',
            count($earningsRows),
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        ));

        $success = 0;
        $errors = 0;

        foreach ($earningsRows as $earnings) {
            $currentSymbol = $earnings->getAccion()->getSymbol();

            try {
                $envelope = $this->commandBus->dispatch(new FetchLatestEarningsReportMessage($currentSymbol));
                $report = $envelope->last(HandledStamp::class)?->getResult();

                $io->writeln(sprintf(
                    '  ✓ %s -> %s %s',
                    $currentSymbol,
                    $report->getFormType(),
                    $report->getFilingDate()?->format('Y-m-d') ?? 'sin fecha'
                ));
                $success++;
            } catch (\Throwable $e) {
                $io->warning(sprintf('  ! %s -> %s', $currentSymbol, $e->getMessage()));
                $errors++;
            }
        }

        if ($errors > 0) {
            $io->warning(sprintf('Completado con incidencias. OK=%d ERROR=%d', $success, $errors));
            return Command::FAILURE;
        }

        $io->success(sprintf('Completado. %d reporte(s) guardado(s).', $success));
        return Command::SUCCESS;
    }

    private function fetchSingleSymbol(string $symbol, SymfonyStyle $io): int
    {
        try {
            $envelope = $this->commandBus->dispatch(new FetchLatestEarningsReportMessage($symbol));
            $report = $envelope->last(HandledStamp::class)?->getResult();

            $io->success(sprintf(
                '%s guardado: %s %s',
                $symbol,
                $report->getFormType(),
                $report->getFilingDate()?->format('Y-m-d') ?? 'sin fecha'
            ));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
