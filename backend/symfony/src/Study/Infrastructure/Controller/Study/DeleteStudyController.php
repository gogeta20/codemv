<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\Delete\DeleteStudyCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeleteStudyController extends ApiController
{
    #[Route('/api/studies/{uuid}', methods: ['DELETE'])]
    public function __invoke(string $uuid): Response
    {
        $this->commandBus->dispatch(new DeleteStudyCommand($uuid));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
