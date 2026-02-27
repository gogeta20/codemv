<?php

namespace App\Study\Infrastructure\Http\Controller\Study;

use App\Study\Application\Delete\DeleteStudyCommand;
use App\Study\Application\Shared\StudyCommandResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class DeleteStudyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    #[Route('/api/studies/{uuid}', methods: ['DELETE'])]
    public function __invoke(string $uuid): Response
    {
        $envelope = $this->commandBus->dispatch(new DeleteStudyCommand($uuid));

        /** @var StudyCommandResult $result */
        $result = $envelope->last(HandledStamp::class)->getResult();

        if (!$result->success) {
            return $this->json(['error' => $result->error], $result->statusCode);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
