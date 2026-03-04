<?php

namespace App\ActiveDirectory\Infrastructure\Controller\Organization;

use App\ActiveDirectory\Application\Organization\SearchOrganizations\SearchOrganizationsQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class SearchOrganizationsController extends ApiController
{
    #[Route('/api/ad/organizations/search', methods: ['GET'], priority: 1)]
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');

        if (empty(trim($q))) {
            return $this->json(['error' => 'Query parameter "q" is required'], Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(new SearchOrganizationsQuery($q));

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
