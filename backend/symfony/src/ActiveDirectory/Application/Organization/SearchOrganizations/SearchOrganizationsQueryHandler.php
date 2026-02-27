<?php

namespace App\ActiveDirectory\Application\Organization\SearchOrganizations;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchOrganizationsQueryHandler
{
    public function __construct(
        private readonly SearchOrganizationsUseCase $useCase,
    ) {}

    public function __invoke(SearchOrganizationsQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
