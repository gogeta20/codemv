<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\Create\CreatePortafolioCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class CreatePortafolioController extends ApiController
{
    #[Route('/api/portafolio', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['nombre'])) {
            return $this->json(['error' => 'nombre is required'], Response::HTTP_BAD_REQUEST);
        }

        $envelope  = $this->commandBus->dispatch(new CreatePortafolioCommand(
            nombre:      trim($body['nombre']),
            descripcion: $body['descripcion'] ?? null,
            isDefault:   (bool) ($body['is_default'] ?? false),
        ));
        $portafolio = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($portafolio->toArray(), Response::HTTP_CREATED);
    }
}
