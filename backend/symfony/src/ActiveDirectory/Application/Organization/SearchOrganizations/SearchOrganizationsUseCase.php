<?php

namespace App\ActiveDirectory\Application\Organization\SearchOrganizations;

use App\ActiveDirectory\Domain\Repository\OrganizationRepositoryInterface;

final class SearchOrganizationsUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function execute(SearchOrganizationsQuery $query): array
    {
        return $this->repository->search($query->query);
    }
}
