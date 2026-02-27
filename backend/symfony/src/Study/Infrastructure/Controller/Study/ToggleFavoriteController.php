<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\ToggleFavorite\ToggleFavoriteCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ToggleFavoriteController extends ApiController
{
    #[Route('/api/studies/{uuid}/favorite', methods: ['POST'])]
    public function __invoke(string $uuid): Response
    {
        $this->commandBus->dispatch(new ToggleFavoriteCommand($uuid));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
