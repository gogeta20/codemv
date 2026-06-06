<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\UpdateAccion\UpdateAccionCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class UpdateAccionController extends ApiController
{
    #[Route('/api/portafolio/{portafolioUuid}/acciones/{entryUuid}', methods: ['PATCH'])]
    public function __invoke(Request $request, string $portafolioUuid, string $entryUuid): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $envelope = $this->commandBus->dispatch(new UpdateAccionCommand(
                entryUuid:        $entryUuid,
                status:           $body['status'] ?? null,
                precioReferencia: isset($body['precio_referencia']) ? (float) $body['precio_referencia'] : null,
                notas:            $body['notas'] ?? null,
            ));
            $entry = $envelope->last(HandledStamp::class)->getResult();
            return $this->json($entry->toArray(), Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
