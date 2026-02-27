<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\Update\UpdateStudyCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateStudyController extends ApiController
{
    #[Route('/api/studies/{uuid}', methods: ['PUT'])]
    public function __invoke(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $this->commandBus->dispatch(new UpdateStudyCommand(
            uuid:       $uuid,
            title:      $data['title'] ?? null,
            content:    $data['content'] ?? null,
            setSummary: array_key_exists('summary', $data),
            summary:    $data['summary'] ?? null,
            category:   $data['category'] ?? null,
            tags:       $data['tags'] ?? null,
            status:     $data['status'] ?? null,
        ));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
