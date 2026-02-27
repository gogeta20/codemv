<?php

namespace App\Study\Infrastructure\Http\Controller\Study;

use App\Study\Application\Create\CreateStudyCommand;
use App\Study\Application\Shared\StudyCommandResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class CreateStudyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {}

    #[Route('/api/studies', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['content']) || empty($data['category'])) {
            return $this->json(['error' => 'title, content and category are required'], 400);
        }

        $envelope = $this->commandBus->dispatch(new CreateStudyCommand(
            title:    $data['title'],
            content:  $data['content'],
            category: $data['category'],
            summary:  $data['summary'] ?? null,
            tags:     $data['tags'] ?? [],
            status:   $data['status'] ?? null,
        ));

        /** @var StudyCommandResult $result */
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            $result->success ? ['data' => $result->data] : ['error' => $result->error],
            $result->statusCode,
        );
    }
}
