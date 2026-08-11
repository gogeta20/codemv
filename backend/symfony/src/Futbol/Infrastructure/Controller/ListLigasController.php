<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Liga\ListLigas\ListLigasQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListLigasController extends ApiController
{
    #[Route('/api/futbol/ligas', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListLigasQuery());
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
