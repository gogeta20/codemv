<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Earnings\GetLatestReport\GetLatestEarningsReportQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetLatestEarningsReportController extends ApiController
{
    #[Route('/api/acciones/earnings-report', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $symbol = trim((string) $request->query->get('symbol', ''));
        if ($symbol === '') {
            return $this->json(['error' => 'symbol es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->queryBus->dispatch(new GetLatestEarningsReportQuery($symbol));
            $result = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
