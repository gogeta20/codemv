<?php

namespace App\Futbol\Application\Liga\GetLigasGpm;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetLigasGpmQueryHandler
{
    public function __construct(private readonly GetLigasGpmUseCase $useCase) {}

    public function __invoke(GetLigasGpmQuery $query): array
    {
        return $this->useCase->execute();
    }
}
