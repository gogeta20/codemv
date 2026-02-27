<?php

namespace App\Study\Infrastructure\Http\Controller\Study;

use App\Study\Application\Find\FindStudyQuery;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class GetStudyContextController extends AbstractController
{
    public function __construct(
        #[Target('query.bus')] private readonly MessageBusInterface $queryBus,
    ) {}

    #[Route('/api/studies/{uuid}/ctx', methods: ['GET'])]
    public function __invoke(string $uuid): Response
    {
        $envelope = $this->queryBus->dispatch(new FindStudyQuery($uuid));
        /** @var Study|null $study */
        $study = $envelope->last(HandledStamp::class)->getResult();

        if ($study === null) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return new Response($study->toContext(), 200, ['Content-Type' => 'text/plain']);
    }
}
