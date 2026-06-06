<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Accion\Update\UpdateAccionCommand;
use App\Acciones\Domain\Exception\AccionNotFoundException;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class UpdateAccionController extends ApiController
{
    #[Route('/api/acciones/{uuid}', methods: ['PATCH'])]
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $envelope = $this->commandBus->dispatch(new UpdateAccionCommand(
                uuid:              $uuid,
                name:              $body['name'] ?? null,
                isActive:          isset($body['is_active']) ? (bool) $body['is_active'] : null,
                alertThresholdPct: isset($body['alert_threshold_pct']) ? (float) $body['alert_threshold_pct'] : null,
                sector:            $body['sector'] ?? null,
                industry:          $body['industry'] ?? null,
                exchange:          $body['exchange'] ?? null,
            ));

            $accion = $envelope->last(HandledStamp::class)->getResult();

            return $this->json($accion->toArray(), Response::HTTP_OK);
        } catch (AccionNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
