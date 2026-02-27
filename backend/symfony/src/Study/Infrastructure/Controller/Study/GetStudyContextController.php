<?php

namespace App\Study\Infrastructure\Controller\Study;

use App\Study\Application\Find\FindStudyQuery;
use App\Study\Application\Shared\StudyReadModel;
use App\Shared\Infrastructure\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetStudyContextController extends ApiController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/api/studies/{uuid}/ctx', methods: ['GET'])]
    public function __invoke(string $uuid): Response
    {
        $envelope = $this->queryBus->dispatch(new FindStudyQuery($uuid));

        /** @var StudyReadModel $readModel */
        $readModel = $envelope->last(HandledStamp::class)->getResult();

        return new Response($readModel->toContext(), Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
