<?php

namespace App\Futbol\Application\Liga\ToggleLigaActiva;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolLiga;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ToggleLigaActivaCommandHandler
{
    public function __construct(private readonly ToggleLigaActivaUseCase $useCase) {}

    public function __invoke(ToggleLigaActivaCommand $command): FutbolLiga
    {
        return $this->useCase->execute($command);
    }
}
