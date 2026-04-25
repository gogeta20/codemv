<?php

namespace App\Identity\Application\PasswordRecovery\ListTokens;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class ListTokensQueryHandler
{
    public function __invoke(ListTokensQuery $query): array
    {
        // Build command arguments
        $args = [
            'docker',
            'exec',
            'identity.service',
            'php',
            'bin/console',
            'identity:test:password-recovery:show-tokens',
        ];

        if ($query->showAll) {
            $args[] = '--all';
        }

        $process = new Process($args);
        $process->run();

        $output = $process->getOutput();
        $tokens = [];

        // Parse table output to extract tokens
        // Looking for lines like: "19befe070a368d6d...   4d9b1ba7-ea1b-11f0-a4a9-66e51db3f3c6   2026-03-13 08:11:29 ..."
        if (preg_match_all('/(\w{16})\.{3}\s+([\w-]{36})\s+(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+([^│]+)\s+(✅|❌)/', $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tokens[] = [
                    'tokenHashShort' => $match[1],
                    'userUuid' => $match[2],
                    'createdAt' => $match[3],
                    'expiresAt' => $match[4],
                    'timeLeft' => trim($match[5]),
                    'status' => $match[6] === '✅' ? 'active' : 'expired',
                ];
            }
        }

        return [
            'success' => true,
            'data' => [
                'tokens' => $tokens,
                'count' => count($tokens),
            ],
            'output' => $output,
        ];
    }
}
