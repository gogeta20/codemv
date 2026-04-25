<?php

namespace App\Identity\Infrastructure\Controller\Pipeline;

use App\Identity\Application\Pipeline\Publish\PublishPipelineCommand;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class PublishPipelineController extends ApiController
{
    #[Route('/api/identity/pipeline/publish', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new PublishPipelineCommand());
        $results  = $envelope->last(HandledStamp::class)->getResult();

        $allOk = array_reduce($results, fn(bool $carry, array $step) => $carry && $step['success'], true);

        return $this->json(['data' => $results], $allOk ? 200 : 207);
    }
}
