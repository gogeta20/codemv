<?php

namespace App\ActiveDirectory\Application\Organization\ListOrganizations;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListOrganizationsQueryHandler
{
    public function __construct(
        private readonly ListOrganizationsUseCase $useCase,
    ) {}

    public function __invoke(ListOrganizationsQuery $query): array
    {
        return $this->useCase->execute();
    }
}
