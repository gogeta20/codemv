<?php

namespace App\Acciones\Application\Portafolio\Update;

use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdatePortafolioCommandHandler
{
    public function __construct(private readonly UpdatePortafolioUseCase $useCase) {}

    public function __invoke(UpdatePortafolioCommand $command): Portafolio
    {
        return $this->useCase->execute($command);
    }
}
