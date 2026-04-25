<?php

namespace App\Identity\Application\PasswordRecovery\Reset;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class ResetPasswordCommandHandler
{
    public function __invoke(ResetPasswordCommand $command): array
    {
        // Execute docker command to run Identity console command
        $process = new Process([
            'docker',
            'exec',
            'identity.service',
            'php',
            'bin/console',
            'identity:test:password-recovery:reset',
            $command->token,
            $command->newPassword,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            // Check if it's a token validation error
            if (str_contains($output, 'Invalid or expired token')) {
                return [
                    'success' => false,
                    'error' => 'Token is invalid or has expired',
                    'output' => $output,
                ];
            }

            return [
                'success' => false,
                'error' => $errorOutput ?: 'Failed to reset password',
                'output' => $output,
            ];
        }

        $output = $process->getOutput();

        // Parse output to extract user UUID
        $userUuid = null;
        if (preg_match('/User UUID\s+([\w-]+)/', $output, $matches)) {
            $userUuid = $matches[1];
        }

        return [
            'success' => true,
            'data' => [
                'userUuid' => $userUuid,
                'message' => 'Password reset successfully',
            ],
            'output' => $output,
        ];
    }
}
