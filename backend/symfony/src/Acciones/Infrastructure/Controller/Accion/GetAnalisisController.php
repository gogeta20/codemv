<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Analisis\GetAnalisis\GetAnalisisQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetAnalisisController extends ApiController
{
    #[Route('/api/acciones/{uuid}/analisis', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetAnalisisQuery($uuid));
        $analisis = $envelope->last(HandledStamp::class)->getResult();

        if ($analisis === null) {
            return $this->json(['error' => 'Sin análisis para esta acción'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($analisis->toArray());
    }
}
