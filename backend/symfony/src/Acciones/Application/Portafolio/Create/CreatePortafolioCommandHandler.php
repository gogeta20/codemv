<?php

namespace App\Acciones\Application\Portafolio\Create;

use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreatePortafolioCommandHandler
{
    public function __construct(private readonly CreatePortafolioUseCase $useCase) {}

    public function __invoke(CreatePortafolioCommand $command): Portafolio
    {
        return $this->useCase->execute($command);
    }
}
