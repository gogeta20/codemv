<?php

namespace App\Acciones\Application\Analisis\AnalizarAccion;

use App\Acciones\Infrastructure\Doctrine\Entity\AccionAnalisis;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AnalizarAccionCommandHandler
{
    public function __construct(
        private readonly AnalizarAccionUseCase $useCase,
    ) {}

    public function __invoke(AnalizarAccionCommand $command): AccionAnalisis
    {
        return $this->useCase->execute($command);
    }
}
