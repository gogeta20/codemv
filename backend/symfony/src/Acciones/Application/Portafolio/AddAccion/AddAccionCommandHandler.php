<?php

namespace App\Acciones\Application\Portafolio\AddAccion;

use App\Acciones\Infrastructure\Doctrine\Entity\PortafolioAccion;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AddAccionCommandHandler
{
    public function __construct(private readonly AddAccionUseCase $useCase) {}

    public function __invoke(AddAccionCommand $command): PortafolioAccion
    {
        return $this->useCase->execute($command);
    }
}
