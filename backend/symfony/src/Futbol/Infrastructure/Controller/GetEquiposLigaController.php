<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Liga\GetEquiposLiga\GetEquiposLigaQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetEquiposLigaController extends ApiController
{
    #[Route('/api/futbol/ligas/{codigo}/equipos', methods: ['GET'], requirements: ['codigo' => '[a-z]{2,4}\.\d'])]
    public function __invoke(string $codigo): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetEquiposLigaQuery($codigo));
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
