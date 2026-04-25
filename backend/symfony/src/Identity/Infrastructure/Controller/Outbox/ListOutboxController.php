<?php

namespace App\Identity\Infrastructure\Controller\Outbox;

use App\Identity\Application\Outbox\List\ListOutboxQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListOutboxController extends ApiController
{
    #[Route('/api/identity/outbox', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) ($request->query->get('limit', 50))));

        $envelope = $this->queryBus->dispatch(new ListOutboxQuery($limit));

        $data = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $data]);
    }
}
