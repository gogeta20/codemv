<?php

namespace App\Acciones\Application\Portafolio\UpdateAccion;

use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateAccionCommandHandler
{
    public function __construct(private readonly UpdateAccionUseCase $useCase) {}

    public function __invoke(UpdateAccionCommand $command): PortafolioAccion
    {
        return $this->useCase->execute($command);
    }
}
