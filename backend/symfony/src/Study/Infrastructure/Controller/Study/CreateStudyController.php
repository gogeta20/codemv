<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\Create\CreateStudyCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateStudyController extends ApiController
{
    #[Route('/api/studies', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['uuid']) || empty($data['title']) || empty($data['content']) || empty($data['category'])) {
            return $this->json(['error' => 'uuid, title, content and category are required'], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new CreateStudyCommand(
            uuid:     $data['uuid'],
            title:    $data['title'],
            content:  $data['content'],
            category: $data['category'],
            summary:  $data['summary'] ?? null,
            tags:     $data['tags'] ?? [],
            status:   $data['status'] ?? null,
        ));

        return $this->json(null, Response::HTTP_CREATED);
    }
}
