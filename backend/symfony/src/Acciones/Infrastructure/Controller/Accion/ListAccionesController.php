<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Accion\List\ListAccionesQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListAccionesController extends ApiController
{
    #[Route('/api/acciones', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $onlyActive = $request->query->has('active') ? true : null;

        $envelope = $this->queryBus->dispatch(new ListAccionesQuery($onlyActive));
        $items    = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
