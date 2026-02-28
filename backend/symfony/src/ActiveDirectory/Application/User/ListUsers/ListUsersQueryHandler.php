<?php

namespace App\ActiveDirectory\Application\User\ListUsers;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListUsersQueryHandler
{
    public function __construct(
        private readonly ListUsersUseCase $useCase,
    ) {}

    public function __invoke(ListUsersQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
