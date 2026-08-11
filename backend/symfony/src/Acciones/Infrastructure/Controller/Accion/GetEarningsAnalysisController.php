<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Earnings\GetAnalysis\GetEarningsAnalysisQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetEarningsAnalysisController extends ApiController
{
    #[Route('/api/acciones/{uuid}/earnings-analysis', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $envelope = $this->queryBus->dispatch(new GetEarningsAnalysisQuery($uuid));
            $result = $envelope->last(HandledStamp::class)?->getResult();

            if ($result === null) {
                return $this->json(['error' => 'Sin reporte de earnings para esta acción'], Response::HTTP_NOT_FOUND);
            }

            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
