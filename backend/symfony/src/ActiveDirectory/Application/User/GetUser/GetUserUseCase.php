<?php

namespace App\ActiveDirectory\Application\User\GetUser;

use App\ActiveDirectory\Domain\Exception\UserNotFoundException;
use App\ActiveDirectory\Domain\Repository\UserRepositoryInterface;

final class GetUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    /** @return array<string, mixed> */
    public function execute(GetUserQuery $query): array
    {
        $data = $this->repository->findBySamAccountName($query->samAccountName);

        if ($data === null) {
            throw new UserNotFoundException($query->samAccountName);
        }

        return $data;
    }
}
