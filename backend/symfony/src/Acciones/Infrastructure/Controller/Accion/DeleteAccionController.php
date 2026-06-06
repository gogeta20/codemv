<?php

namespace App\Acciones\Infrastructure\Controller\Accion;

use App\Acciones\Application\Accion\Delete\DeleteAccionCommand;
use App\Acciones\Domain\Exception\AccionNotFoundException;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeleteAccionController extends ApiController
{
    #[Route('/api/acciones/{uuid}', methods: ['DELETE'])]
    public function __invoke(string $uuid): Response
    {
        try {
            $this->commandBus->dispatch(new DeleteAccionCommand($uuid));
            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (AccionNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
