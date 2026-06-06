<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\RemoveAccion\RemoveAccionCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RemoveAccionController extends ApiController
{
    #[Route('/api/portafolio/{portafolioUuid}/acciones/{entryUuid}', methods: ['DELETE'])]
    public function __invoke(string $portafolioUuid, string $entryUuid): Response
    {
        try {
            $this->commandBus->dispatch(new RemoveAccionCommand($entryUuid));
            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
