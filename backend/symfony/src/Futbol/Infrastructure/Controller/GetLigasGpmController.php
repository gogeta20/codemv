<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Liga\GetLigasGpm\GetLigasGpmQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetLigasGpmController extends ApiController
{
    #[Route('/api/futbol/ligas/gpm', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLigasGpmQuery());
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
