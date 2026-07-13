<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Equipo\GetAnalisis\GetEquipoAnalisisQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetEquipoAnalisisController extends ApiController
{
    #[Route('/api/futbol/equipos/{teamId}/analisis', methods: ['GET'], requirements: ['teamId' => '\d+'])]
    public function __invoke(string $teamId, Request $request): JsonResponse
    {
        $liga = trim((string) $request->query->get('liga', ''));
        if ($liga === '') {
            return $this->json(['error' => 'Parámetro liga requerido'], Response::HTTP_BAD_REQUEST);
        }

        $season = $request->query->get('season');
        if ($season !== null && $season !== '' && !ctype_digit((string) $season)) {
            return $this->json(['error' => 'Parámetro season inválido'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->queryBus->dispatch(new GetEquipoAnalisisQuery(
                $teamId,
                $liga,
                $season !== null && $season !== '' ? (int) $season : null,
            ));
            $result = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
