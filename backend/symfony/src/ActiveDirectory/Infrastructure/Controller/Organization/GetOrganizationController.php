<?php

namespace App\ActiveDirectory\Infrastructure\Controller\Organization;

use App\ActiveDirectory\Application\Organization\GetOrganization\GetOrganizationQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetOrganizationController extends ApiController
{
    #[Route('/api/ad/organizations/{code}', methods: ['GET'])]
    public function __invoke(string $code): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetOrganizationQuery($code));

        $data = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $data]);
    }
}
