<?php

namespace App\Futbol\Infrastructure\Controller\Favorito;

use App\Futbol\Application\Favorito\GetPartidos\GetFavoritosPartidosQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetFavoritosPartidosController extends ApiController
{
    #[Route('/api/futbol/favoritos/partidos', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetFavoritosPartidosQuery());
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
