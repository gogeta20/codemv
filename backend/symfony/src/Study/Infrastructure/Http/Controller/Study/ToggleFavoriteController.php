<?php

namespace App\Study\Infrastructure\Http\Controller\Study;

use App\Study\Application\Shared\StudyCommandResult;
use App\Study\Application\ToggleFavorite\ToggleFavoriteCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class ToggleFavoriteController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    #[Route('/api/studies/{uuid}/favorite', methods: ['POST'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new ToggleFavoriteCommand($uuid));

        /** @var StudyCommandResult $result */
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            $result->success ? ['data' => $result->data] : ['error' => $result->error],
            $result->statusCode,
        );
    }
}
