<?php

namespace App\Identity\Application\PasswordRecovery\Request;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class RequestPasswordRecoveryCommandHandler
{
    public function __invoke(RequestPasswordRecoveryCommand $command): array
    {
        // Execute docker command to run Identity console command
        $process = new Process([
            'docker',
            'exec',
            'identity.service',
            'php',
            'bin/console',
            'identity:test:password-recovery:request',
            $command->email,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            return [
                'success' => false,
                'error' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
            ];
        }

        $output = $process->getOutput();

        // Parse output to extract token
        $token = null;
        $userUuid = null;
        $recoveryUrl = null;

        if (preg_match('/Raw Token\s+(\w+)/', $output, $matches)) {
            $token = $matches[1];
        }

        if (preg_match('/User UUID\s+([\w-]+)/', $output, $matches)) {
            $userUuid = $matches[1];
        }

        if (preg_match('/Recovery URL\s+(http[^\s]+)/', $output, $matches)) {
            $recoveryUrl = $matches[1];
        }

        return [
            'success' => true,
            'data' => [
                'email' => $command->email,
                'userUuid' => $userUuid,
                'rawToken' => $token,
                'recoveryUrl' => $recoveryUrl,
            ],
            'output' => $output,
        ];
    }
}
