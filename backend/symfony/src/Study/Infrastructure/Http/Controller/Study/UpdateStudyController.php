<?php

namespace App\Study\Infrastructure\Http\Controller\Study;

use App\Study\Application\Shared\StudyCommandResult;
use App\Study\Application\Update\UpdateStudyCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class UpdateStudyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    #[Route('/api/studies/{uuid}', methods: ['PUT'])]
    public function __invoke(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $envelope = $this->commandBus->dispatch(new UpdateStudyCommand(
            uuid:       $uuid,
            title:      $data['title'] ?? null,
            content:    $data['content'] ?? null,
            setSummary: array_key_exists('summary', $data),
            summary:    $data['summary'] ?? null,
            category:   $data['category'] ?? null,
            tags:       $data['tags'] ?? null,
            status:     $data['status'] ?? null,
        ));

        /** @var StudyCommandResult $result */
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            $result->success ? ['data' => $result->data] : ['error' => $result->error],
            $result->statusCode,
        );
    }
}
