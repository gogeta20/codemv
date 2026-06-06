<?php

namespace App\Futbol\Application\Favorito\Add;

use App\Futbol\Infrastructure\Doctrine\Entity\FutbolFavorito;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class AddFavoritoCommandHandler
{
    public function __construct(private readonly AddFavoritoUseCase $useCase) {}

    public function __invoke(AddFavoritoCommand $command): FutbolFavorito
    {
        return $this->useCase->execute($command);
    }
}
