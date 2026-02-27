<?php

namespace App\ActiveDirectory\Application\Organization\GetOrganization;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrganizationQueryHandler
{
    public function __construct(
        private readonly GetOrganizationUseCase $useCase,
    ) {}

    /** @return array<string, mixed> */
    public function __invoke(GetOrganizationQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}
