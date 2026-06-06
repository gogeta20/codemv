<?php

namespace App\Futbol\Application\Liga\GetEquiposLiga;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEquiposLigaQueryHandler
{
    public function __construct(private readonly GetEquiposLigaUseCase $useCase) {}

    public function __invoke(GetEquiposLigaQuery $query): array
    {
        return $this->useCase->execute($query->codigo);
    }
}
