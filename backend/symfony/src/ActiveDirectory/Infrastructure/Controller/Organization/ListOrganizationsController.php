<?php

namespace App\ActiveDirectory\Infrastructure\Controller\Organization;

use App\ActiveDirectory\Application\Organization\ListOrganizations\ListOrganizationsQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListOrganizationsController extends ApiController
{
    #[Route('/api/ad/organizations', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListOrganizationsQuery());

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
