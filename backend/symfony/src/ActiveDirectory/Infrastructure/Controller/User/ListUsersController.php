<?php

namespace App\ActiveDirectory\Infrastructure\Controller\User;

use App\ActiveDirectory\Application\User\ListUsers\ListUsersQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListUsersController extends ApiController
{
    #[Route('/api/ad/organizations/{organizationCode}/users', methods: ['GET'])]
    public function __invoke(string $organizationCode): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListUsersQuery($organizationCode));

        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $items, 'total' => count($items)]);
    }
}
