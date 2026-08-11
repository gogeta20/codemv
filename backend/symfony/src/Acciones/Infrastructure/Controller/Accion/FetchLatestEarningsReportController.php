<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Earnings\FetchLatestReport\FetchLatestEarningsReportCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class FetchLatestEarningsReportController extends ApiController
{
    #[Route('/api/acciones/earnings-report/fetch', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $symbol = strtoupper(trim((string) ($body['symbol'] ?? '')));

        if ($symbol === '') {
            return $this->json(['error' => 'symbol es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->commandBus->dispatch(new FetchLatestEarningsReportCommand($symbol));
            $report = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json($report->toArray(), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
