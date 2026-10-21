<?php

use App\Strategies\McpJsonStrategy;
use Cli\Console\SymfonyConsole;
use Framework\Emitters\SapiEmitter;
use Framework\Routers\LeagueRouter;

return [
    'strategy' => McpJsonStrategy::class,
    'console' => SymfonyConsole::class,
    'emitter' => SapiEmitter::class,
    'router' => LeagueRouter::class,
];
