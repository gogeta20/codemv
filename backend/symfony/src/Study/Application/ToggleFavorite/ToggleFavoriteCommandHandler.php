<?php

namespace App\Study\Application\ToggleFavorite;

use App\Study\Application\Shared\StudyCommandResult;
use App\Study\Domain\Exception\StudyNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ToggleFavoriteCommandHandler
{
    public function __construct(
        private readonly ToggleFavoriteUseCase $useCase,
    ) {}

    public function __invoke(ToggleFavoriteCommand $command): StudyCommandResult
    {
        try {
            $study = $this->useCase->execute($command);
            return StudyCommandResult::ok(['is_favorite' => $study->isFavorite()]);
        } catch (StudyNotFoundException) {
            return StudyCommandResult::notFound();
        }
    }
}
