<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Seleccion\GetMundial\GetMundialSeleccionQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetMundialSeleccionController extends ApiController
{
    #[Route('/api/futbol/mundial/seleccion', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $fechaStr = $request->query->get('fecha');
        $fecha    = null;

        if ($fechaStr) {
            try {
                $fecha = new \DateTimeImmutable($fechaStr);
            } catch (\Exception) {
                return $this->json(['error' => 'Fecha inválida. Formato esperado: YYYY-MM-DD'], 400);
            }
        }

        $envelope = $this->queryBus->dispatch(new GetMundialSeleccionQuery($fecha));
        $result   = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $result]);
    }
}
