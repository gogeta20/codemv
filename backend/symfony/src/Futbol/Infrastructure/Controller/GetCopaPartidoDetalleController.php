<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Copa\Detail\GetCopaPartidoDetalleQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class GetCopaPartidoDetalleController extends ApiController
{
    #[Route('/api/futbol/copas/{competition}/{eventId}', methods: ['GET'])]
    public function __invoke(string $competition, string $eventId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetCopaPartidoDetalleQuery($competition, $eventId));
        $item = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $item]);
    }
}
