<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Partido\GetDetalle\GetPartidoDetalleQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetPartidoDetalleController extends ApiController
{
    #[Route('/api/futbol/partidos/{uuid}', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetPartidoDetalleQuery($uuid));
        $partido  = $envelope->last(HandledStamp::class)->getResult();

        if ($partido === null) {
            return $this->json(['error' => 'Partido no encontrado'], 404);
        }

        return $this->json(['data' => $partido->toArray()]);
    }
}
