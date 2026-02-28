<?php

namespace App\ActiveDirectory\Application\User\ListUsers;

use App\ActiveDirectory\Domain\Repository\UserRepositoryInterface;

final class ListUsersUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(ListUsersQuery $query): array
    {
        return $this->repository->findByOrganization($query->organizationCode);
    }
}
