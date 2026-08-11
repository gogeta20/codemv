<?php

namespace App\Futbol\Infrastructure\Controller;

use App\Futbol\Application\Liga\ToggleLigaActiva\ToggleLigaActivaCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ToggleLigaActivaController extends ApiController
{
    #[Route('/api/futbol/ligas/{codigo}', methods: ['PATCH'], requirements: ['codigo' => '[a-z]{2,4}\.[a-z0-9]+'])]
    public function __invoke(string $codigo, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (!array_key_exists('activa', $body)) {
            return $this->json(['error' => 'activa es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->commandBus->dispatch(new ToggleLigaActivaCommand(
            codigoEspn: $codigo,
            activa:     (bool) $body['activa'],
            nombre:     $body['nombre'] ?? $codigo,
            pais:       $body['pais'] ?? '',
        ));

        $liga = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($liga->toArray());
    }
}
