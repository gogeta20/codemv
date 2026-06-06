<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\GetPortafolio\GetPortafolioQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetPortafolioController extends ApiController
{
    #[Route('/api/portafolio/{uuid}', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $envelope = $this->queryBus->dispatch(new GetPortafolioQuery($uuid));
            $result   = $envelope->last(HandledStamp::class)->getResult();
            return $this->json($result);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
