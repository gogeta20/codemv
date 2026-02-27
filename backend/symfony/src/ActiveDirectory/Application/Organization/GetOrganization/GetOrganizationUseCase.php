<?php

namespace App\ActiveDirectory\Application\Organization\GetOrganization;

use App\ActiveDirectory\Domain\Exception\OrganizationNotFoundException;
use App\ActiveDirectory\Domain\Repository\OrganizationRepositoryInterface;
use App\ActiveDirectory\Domain\ValueObject\OrganizationCode;

final class GetOrganizationUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    /** @return array<string, mixed> */
    public function execute(GetOrganizationQuery $query): array
    {
        $code = new OrganizationCode($query->code);
        $data = $this->repository->findByCodeWithFullData($code);

        if ($data === null) {
            throw new OrganizationNotFoundException($query->code);
        }

        return $data;
    }
}
