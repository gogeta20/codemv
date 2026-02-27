<?php

namespace App\Study\Infrastructure\Controller\Tag;

use App\Study\Application\Tag\Create\CreateTagCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateTagController extends ApiController
{
    #[Route('/api/tags', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['slug'])) {
            return $this->json(['error' => 'slug is required'], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new CreateTagCommand(
            slug: $data['slug'],
            name: $data['name'] ?? null,
        ));

        return $this->json(null, Response::HTTP_CREATED);
    }
}
