<?php

namespace App\ActiveDirectory\Application\User\GetUser;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserQueryHandler
{
    public function __construct(
        private readonly GetUserUseCase $useCase,
    ) {}

    /** @return array<string, mixed> */
    public function __invoke(GetUserQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
