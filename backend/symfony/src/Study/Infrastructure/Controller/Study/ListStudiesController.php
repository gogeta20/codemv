<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\List\ListStudiesQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListStudiesController extends ApiController
{
    #[Route('/api/studies', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListStudiesQuery(
            category: $request->query->get('category'),
            tags:     $request->query->get('tags'),
            favorite: $request->query->has('favorite'),
            status:   $request->query->get('status'),
        ));

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
