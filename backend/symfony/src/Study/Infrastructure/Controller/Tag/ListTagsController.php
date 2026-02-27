<?php

namespace App\Study\Infrastructure\Controller\Tag;

use App\Study\Application\Tag\List\ListTagsQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListTagsController extends ApiController
{
    #[Route('/api/tags', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListTagsQuery());

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items]);
    }
}
