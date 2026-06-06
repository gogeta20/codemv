<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\AddAccion\AddAccionCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class AddAccionController extends ApiController
{
    #[Route('/api/portafolio/{uuid}/acciones', methods: ['POST'])]
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['accion_uuid'])) {
            return $this->json(['error' => 'accion_uuid is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->commandBus->dispatch(new AddAccionCommand(
                portafolioUuid:   $uuid,
                accionUuid:       $body['accion_uuid'],
                status:           $body['status'] ?? 'watchlist',
                precioReferencia: isset($body['precio_referencia']) ? (float) $body['precio_referencia'] : null,
                notas:            $body['notas'] ?? null,
            ));
            $entry = $envelope->last(HandledStamp::class)->getResult();
            return $this->json($entry->toArray(), Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
