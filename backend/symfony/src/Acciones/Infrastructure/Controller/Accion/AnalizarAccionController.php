<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Analisis\AnalizarAccion\AnalizarAccionCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class AnalizarAccionController extends ApiController
{
    #[Route('/api/acciones/{uuid}/analisis', methods: ['POST'])]
    public function __invoke(string $uuid, Request $request): JsonResponse
    {
        $incomeFile   = $request->files->get('income');
        $balanceFile  = $request->files->get('balance');
        $cashflowFile = $request->files->get('cashflow');

        if (!$incomeFile || !$balanceFile || !$cashflowFile) {
            return $this->json(
                ['error' => 'Se requieren 3 archivos CSV: income, balance, cashflow'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $envelope = $this->commandBus->dispatch(new AnalizarAccionCommand(
                accionUuid:      $uuid,
                incomeContent:   file_get_contents($incomeFile->getPathname()),
                balanceContent:  file_get_contents($balanceFile->getPathname()),
                cashflowContent: file_get_contents($cashflowFile->getPathname()),
                priceAtAnalysis: $request->request->get('price') !== null
                    ? (float) $request->request->get('price')
                    : null,
            ));

            $analisis = $envelope->last(HandledStamp::class)->getResult();

            return $this->json($analisis->toArray(), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
