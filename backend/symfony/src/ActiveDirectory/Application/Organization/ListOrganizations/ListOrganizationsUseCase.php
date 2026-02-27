<?php

namespace App\ActiveDirectory\Application\Organization\ListOrganizations;

use App\ActiveDirectory\Domain\Repository\OrganizationRepositoryInterface;

final class ListOrganizationsUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
