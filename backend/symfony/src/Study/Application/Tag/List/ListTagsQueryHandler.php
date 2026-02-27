<?php

namespace App\Study\Application\Tag\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListTagsQueryHandler
{
    public function __construct(
        private readonly ListTagsUseCase $useCase,
    ) {}

    public function __invoke(ListTagsQuery $query): array
    {
        return $this->useCase->execute();
    }
}
