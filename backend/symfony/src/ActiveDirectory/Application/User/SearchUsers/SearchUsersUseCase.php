<?php

namespace App\ActiveDirectory\Application\User\SearchUsers;

use App\ActiveDirectory\Domain\Repository\UserRepositoryInterface;

final class SearchUsersUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(SearchUsersQuery $query): array
    {
        return $this->repository->search($query->query);
    }
}
