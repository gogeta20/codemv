<?php

namespace App\Study\Application\Find;

use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class FindStudyQueryHandler
{
    public function __construct(
        private readonly FindStudyUseCase $useCase,
    ) {}

    public function __invoke(FindStudyQuery $query): ?Study
    {
        try {
            return $this->useCase->execute($query);
        } catch (StudyNotFoundException) {
            return null;
        }
    }
}
