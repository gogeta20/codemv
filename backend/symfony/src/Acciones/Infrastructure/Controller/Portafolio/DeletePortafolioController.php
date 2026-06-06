<?php

namespace App\Acciones\Infrastructure\Controller\Portafolio;

use App\Acciones\Application\Portafolio\Delete\DeletePortafolioCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeletePortafolioController extends ApiController
{
    #[Route('/api/portafolio/{uuid}', methods: ['DELETE'])]
    public function __invoke(string $uuid): Response
    {
        try {
            $this->commandBus->dispatch(new DeletePortafolioCommand($uuid));
            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
