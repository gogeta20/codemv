<?php

namespace App\Study\Infrastructure\Controller\Category;

use App\Study\Application\Category\List\ListCategoriesQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListCategoriesController extends ApiController
{
    #[Route('/api/categories', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListCategoriesQuery());

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items]);
    }
}
