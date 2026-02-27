<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\Find\FindStudyQuery;
use App\Study\Application\Shared\StudyReadModel;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetStudyController extends ApiController
{
    #[Route('/api/studies/{uuid}', methods: ['GET'])]
    public function __invoke(string $uuid): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new FindStudyQuery($uuid));

        /** @var StudyReadModel $readModel */
        $readModel = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(['data' => $readModel->toArray()]);
    }
}
