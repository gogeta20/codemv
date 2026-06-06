<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\Update\UpdatePortafolioCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class UpdatePortafolioController extends ApiController
{
    #[Route('/api/portafolio/{uuid}', methods: ['PATCH'])]
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $envelope   = $this->commandBus->dispatch(new UpdatePortafolioCommand(
                uuid:        $uuid,
                nombre:      $body['nombre'] ?? null,
                descripcion: $body['descripcion'] ?? null,
                isDefault:   isset($body['is_default']) ? (bool) $body['is_default'] : null,
            ));
            $portafolio = $envelope->last(HandledStamp::class)->getResult();
            return $this->json($portafolio->toArray(), Response::HTTP_OK);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
