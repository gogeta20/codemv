<?php

namespace App\ActiveDirectory\Application\User\SearchUsers;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchUsersQueryHandler
{
    public function __construct(
        private readonly SearchUsersUseCase $useCase,
    ) {}

    public function __invoke(SearchUsersQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
