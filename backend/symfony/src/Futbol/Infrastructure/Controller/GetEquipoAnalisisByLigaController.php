<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Equipo\GetAnalisis\GetEquipoAnalisisQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetEquipoAnalisisByLigaController extends ApiController
{
    #[Route('/api/futbol/ligas/{codigo}/equipos/{teamId}/analisis', methods: ['GET'], requirements: ['codigo' => '[a-z]{2,4}\.\d', 'teamId' => '\d+'])]
    public function __invoke(string $codigo, string $teamId, Request $request): JsonResponse
    {
        $season = $request->query->get('season');
        if ($season !== null && $season !== '' && !ctype_digit((string) $season)) {
            return $this->json(['error' => 'Parámetro season inválido'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->queryBus->dispatch(new GetEquipoAnalisisQuery(
                $teamId,
                $codigo,
                $season !== null && $season !== '' ? (int) $season : null,
            ));
            $result = $envelope->last(HandledStamp::class)->getResult();

            return $this->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
