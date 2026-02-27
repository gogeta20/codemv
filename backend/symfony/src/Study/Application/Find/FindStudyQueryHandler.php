<?php

namespace App\Study\Application\Find;

use App\Study\Application\Shared\StudyReadModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class FindStudyQueryHandler
{
    public function __construct(
        private readonly FindStudyUseCase $useCase,
    ) {}

    public function __invoke(FindStudyQuery $query): StudyReadModel
    {
        return $this->useCase->execute($query);
    }
}
