<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\List\ListPortafolioQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListPortafolioController extends ApiController
{
    #[Route('/api/portafolio', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListPortafolioQuery());
        $items    = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
