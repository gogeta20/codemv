<?php

namespace App\Identity\Infrastructure\Controller\PasswordRecovery;

use App\Identity\Application\PasswordRecovery\Request\RequestPasswordRecoveryCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class RequestPasswordRecoveryController extends ApiController
{
    #[Route('/api/identity/password-recovery/request', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email is required'], 400);
        }

        $envelope = $this->commandBus->dispatch(new RequestPasswordRecoveryCommand($email));
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($result, $result['success'] ? 200 : 500);
    }
}
