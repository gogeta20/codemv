<?php

namespace App\Study\Infrastructure\Controller\Category;

use App\Study\Application\Category\Create\CreateCategoryCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateCategoryController extends ApiController
{
    #[Route('/api/categories', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['slug']) || empty($data['name'])) {
            return $this->json(['error' => 'slug and name are required'], Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(new CreateCategoryCommand(
            slug: $data['slug'],
            name: $data['name'],
        ));

        return $this->json(null, Response::HTTP_CREATED);
    }
}
