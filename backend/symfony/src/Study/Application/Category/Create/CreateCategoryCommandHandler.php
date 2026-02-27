<?php

namespace App\Study\Application\Category\Create;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateCategoryCommandHandler
{
    public function __construct(
        private readonly CreateCategoryUseCase $useCase,
    ) {}

    public function __invoke(CreateCategoryCommand $command): void
    {
        $this->useCase->execute($command);
    }
}
