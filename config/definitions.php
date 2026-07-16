<?php

use App\Strategies\McpJsonStrategy;
use Cli\Commands\HelloWorldCommand;
use Cli\Console\SymfonyConsole;
use Framework\Emitters\SapiEmitter;
use Framework\Routers\LeagueRouter;
use League\Container\Definition\{Definition, DefinitionAggregate};

return new DefinitionAggregate([
    new Definition('dependencies', [
        'strategy' => McpJsonStrategy::class,
        'console'  => SymfonyConsole::class,
        'emitter'  => SapiEmitter::class,
        'router'   => LeagueRouter::class
    ]),
    new Definition('commands', [
        HelloWorldCommand::class,
        \Cli\Commands\MigrateCommand::class
    ]),
    new Definition('mcp.settings', [
        'server_name' => 'mcp-telegram-editor',
        'server_ver'  => '1.0.0',
        'session_dir' => sys_get_temp_dir() . '/mcp-sessions',
        'logging'     => [
            'stream' => 'php://stderr',
        ]
    ]),
    new Definition('mcp.tools', [
        // Добавьте новые инструменты для публикации и сбора данных здесь
    ])
]);