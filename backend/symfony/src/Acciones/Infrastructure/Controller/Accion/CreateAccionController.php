<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Accion\Create\CreateAccionCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class CreateAccionController extends ApiController
{
    #[Route('/api/acciones', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['symbol']) || empty($body['name'])) {
            return $this->json(['error' => 'symbol and name are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->commandBus->dispatch(new CreateAccionCommand(
                symbol:            strtoupper(trim($body['symbol'])),
                name:              trim($body['name']),
                type:              $body['type'] ?? 'stock',
                source:            $body['source'] ?? 'manual',
                alertThresholdPct: (float) ($body['alert_threshold_pct'] ?? 5.0),
            sector:            $body['sector'] ?? null,
            industry:          $body['industry'] ?? null,
            exchange:          $body['exchange'] ?? null,
            ));

            $accion = $envelope->last(HandledStamp::class)->getResult();

            return $this->json($accion->toArray(), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
