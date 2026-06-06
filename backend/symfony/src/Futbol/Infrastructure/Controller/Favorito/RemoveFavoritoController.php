<?php

namespace App\Futbol\Infrastructure\Controller\Favorito;

use App\Futbol\Application\Favorito\Remove\RemoveFavoritoCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class RemoveFavoritoController extends ApiController
{
    #[Route('/api/futbol/favoritos/{uuid}', methods: ['DELETE'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new RemoveFavoritoCommand($uuid));
        $found    = $envelope->last(HandledStamp::class)->getResult();

        return $found
            ? $this->json(null, Response::HTTP_NO_CONTENT)
            : $this->json(['error' => 'Favorito no encontrado'], Response::HTTP_NOT_FOUND);
    }
}
