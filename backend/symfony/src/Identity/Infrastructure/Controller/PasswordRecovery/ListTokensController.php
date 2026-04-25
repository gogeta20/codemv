<?php

namespace App\Identity\Infrastructure\Controller\PasswordRecovery;

use App\Identity\Application\PasswordRecovery\ListTokens\ListTokensQuery;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ListTokensController extends ApiController
{
    #[Route('/api/identity/password-recovery/tokens', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $showAll = $request->query->getBoolean('all', false);

        $envelope = $this->queryBus->dispatch(new ListTokensQuery($showAll));
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($result, $result['success'] ? 200 : 500);
    }
}
