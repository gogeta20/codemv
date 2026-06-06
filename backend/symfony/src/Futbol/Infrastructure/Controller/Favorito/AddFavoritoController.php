<?php

namespace App\Futbol\Infrastructure\Controller\Favorito;

use App\Futbol\Application\Favorito\Add\AddFavoritoCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class AddFavoritoController extends ApiController
{
    #[Route('/api/futbol/favoritos', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        if (empty($body['espn_team_id']) || empty($body['espn_liga_code']) || empty($body['team_name'])) {
            return $this->json(['error' => 'espn_team_id, espn_liga_code y team_name son obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $envelope = $this->commandBus->dispatch(new AddFavoritoCommand(
                espnTeamId:   $body['espn_team_id'],
                espnLigaCode: $body['espn_liga_code'],
                teamName:     $body['team_name'],
                ligaNombre:   $body['liga_nombre'] ?? '',
                pais:         $body['pais'] ?? '',
            ));

            $favorito = $envelope->last(HandledStamp::class)->getResult();

            return $this->json($favorito->toArray(), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'El equipo ya está en favoritos'], Response::HTTP_CONFLICT);
        }
    }
}
