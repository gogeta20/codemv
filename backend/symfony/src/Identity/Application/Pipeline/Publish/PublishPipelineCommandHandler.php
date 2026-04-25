<?php

namespace App\Identity\Application\Pipeline\Publish;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class PublishPipelineCommandHandler
{
    public function __construct(
        private readonly PublishPipelineUseCase $useCase,
    ) {}

    /** @return array<string, array{label: string, success: bool, output: string, error: string}> */
    public function __invoke(PublishPipelineCommand $command): array
    {
        return $this->useCase->execute();
    }
}
