<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Accion\Find\FindAccionNoticiasQuery;
use App\Acciones\Domain\Exception\AccionNotFoundException;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetAccionNoticiasController extends ApiController
{
    #[Route('/api/acciones/{uuid}/noticias', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $envelope = $this->queryBus->dispatch(new FindAccionNoticiasQuery($uuid));
            $result   = $envelope->last(HandledStamp::class)->getResult();
            return $this->json($result);
        } catch (AccionNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
