<?php

namespace App\Identity\Application\Pipeline\Publish;

use Symfony\Component\Process\Process;

final class PublishPipelineUseCase
{
    private const STEPS = [
        'identity_publish' => [
            'label' => 'identity → RabbitMQ',
            'cmd'   => ['docker', 'exec', 'identity.service', 'php', 'bin/console', 'event-messaging:outbox:publish', 'identity'],
        ],
        'panel_consume' => [
            'label' => 'RabbitMQ → panel inbox',
            'cmd'   => ['docker', 'exec', 'core.panel', 'php', 'bin/console', 'event-messaging:inbox:consume', 'core_from_identity'],
        ],
        'panel_process' => [
            'label' => 'panel inbox → handlers',
            'cmd'   => ['docker', 'exec', 'core.panel', 'php', 'bin/console', 'event-messaging:inbox:process', 'core'],
        ],
    ];

    /** @return array<string, array{label: string, success: bool, output: string, error: string}> */
    public function execute(): array
    {
        $results = [];

        foreach (self::STEPS as $key => $step) {
            $process = new Process($step['cmd']);
            $process->setTimeout(30);
            $process->run();

            $results[$key] = [
                'label'   => $step['label'],
                'success' => $process->isSuccessful(),
                'output'  => trim($process->getOutput()),
                'error'   => trim($process->getErrorOutput()),
            ];
        }

        return $results;
    }
}
