<?php

namespace App\ActiveDirectory\Infrastructure\Controller\User;

use App\ActiveDirectory\Application\User\GetUser\GetUserQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetUserController extends ApiController
{
    #[Route('/api/ad/users/{samAccountName}', methods: ['GET'])]
    public function __invoke(string $samAccountName): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetUserQuery($samAccountName));

        $data = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $data]);
    }
}
