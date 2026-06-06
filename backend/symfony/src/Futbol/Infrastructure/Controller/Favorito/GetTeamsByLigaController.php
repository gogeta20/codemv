<?php

namespace App\Futbol\Infrastructure\Controller\Favorito;

use App\Futbol\Application\Favorito\GetTeamsByLiga\GetTeamsByLigaQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetTeamsByLigaController extends ApiController
{
    #[Route('/api/futbol/teams', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $liga = $request->query->get('liga', '');

        if (empty($liga)) {
            return $this->json(['error' => 'Parámetro liga requerido'], 400);
        }

        $envelope = $this->queryBus->dispatch(new GetTeamsByLigaQuery($liga));
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
