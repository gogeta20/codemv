<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Jugador\GetAnalisis\GetJugadorAnalisisQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetJugadorAnalisisController extends ApiController
{
    #[Route('/api/futbol/jugadores/{playerId}/analisis', methods: ['GET'], requirements: ['playerId' => '\d+'])]
    public function __invoke(string $playerId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetJugadorAnalisisQuery($playerId));
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
