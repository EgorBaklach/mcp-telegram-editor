<?php

use Magistrale\Agents\{AgyAgent, HermesAgent};

return [
    'drivers' => [
        'agy' => AgyAgent::class,
        'hermes' => HermesAgent::class,
    ],
    'timeout' => 600,
    'agy' => [
        'endpoint' => function(string $prompt, string $homeDir, int $timeout = 600)
        {
            return [['agy', '--dangerously-skip-permissions', '--print-timeout', "{$timeout}s", '-p', $prompt], null, ['HTTP_PROXY' => false, 'HTTPS_PROXY' => false, 'HOME' => $homeDir]];
        },
        'links' => [
            '.gemini/AGENTS.md',
            '.gemini/config',
            '.gemini/antigravity-cli/antigravity-oauth-token',
            '.gemini/antigravity-cli/settings.json',
            '.gemini/antigravity-cli/installation_id',
            '.gemini/antigravity-cli/jetski_state.pbtxt',
            '.gemini/antigravity-cli/builtin',
            '.gemini/antigravity-cli/mcp',
            '.gemini/antigravity-cli/cache',
        ],
    ],
    'hermes' => [],
];
