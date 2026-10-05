<?php

namespace App\Futbol\Infrastructure\Command;

use App\Futbol\Application\Copa\Import\SyncCopasPartidosUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:futbol:sync-copas',
    description: 'Descarga y guarda partidos de Champions, Europa League y Conference League en la base de datos.',
)]
final class SyncCopasPartidosCommand extends Command
{
    public function __construct(private readonly SyncCopasPartidosUseCase $useCase)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('fecha', null, InputOption::VALUE_OPTIONAL, 'Fecha base YYYY-MM-DD', (new \DateTimeImmutable('today'))->format('Y-m-d'))
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Cantidad de dias consecutivos a sincronizar desde la fecha base', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fecha = $this->resolveFecha((string) $input->getOption('fecha'));
        if ($fecha === null) {
            $io->error('Fecha invalida. Usa formato YYYY-MM-DD.');
            return Command::FAILURE;
        }

        $days = max(1, (int) $input->getOption('days'));
        $total = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $target = $fecha->modify(sprintf('+%d day', $offset));

            try {
                $result = $this->useCase->execute($target);
                $io->writeln(sprintf('%s -> %d partido(s) guardado(s)', $result['fecha'], $result['saved']));
                $total += (int) $result['saved'];
            } catch (\Throwable $e) {
                $io->warning(sprintf('%s -> %s', $target->format('Y-m-d'), $e->getMessage()));
            }
        }

        $io->success(sprintf('Sincronizacion completada. Total guardado: %d partido(s).', $total));

        return Command::SUCCESS;
    }

    private function resolveFecha(string $value): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
