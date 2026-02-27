<?php

namespace App\Study\Application\Category\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListCategoriesQueryHandler
{
    public function __construct(
        private readonly ListCategoriesUseCase $useCase,
    ) {}

    public function __invoke(ListCategoriesQuery $query): array
    {
        return $this->useCase->execute();
    }
}
