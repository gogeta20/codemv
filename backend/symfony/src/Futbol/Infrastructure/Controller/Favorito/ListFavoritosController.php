<?php

namespace App\Futbol\Infrastructure\Controller\Favorito;

use App\Futbol\Application\Favorito\List\ListFavoritosQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListFavoritosController extends ApiController
{
    #[Route('/api/futbol/favoritos', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListFavoritosQuery());
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
