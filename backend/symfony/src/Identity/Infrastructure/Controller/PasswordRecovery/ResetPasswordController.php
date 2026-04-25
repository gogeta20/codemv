<?php

namespace App\Identity\Infrastructure\Controller\PasswordRecovery;

use App\Identity\Application\PasswordRecovery\Reset\ResetPasswordCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends ApiController
{
    #[Route('/api/identity/password-recovery/reset', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$token || !$newPassword) {
            return $this->json(['error' => 'Token and newPassword are required'], 400);
        }

        $envelope = $this->commandBus->dispatch(new ResetPasswordCommand($token, $newPassword));
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json($result, $result['success'] ? 200 : 400);
    }
}
