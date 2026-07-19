<?php

use App\Strategies\McpJsonStrategy;
use App\Tools\PingTool;
use App\Tools\PublishTool;
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
    new Definition('telegram.configs', [
        'base_uri' => "https://api.telegram.org/bot" . getenv('TELEGRAM_BOT_TOKEN') . "/",
        'chat_id' => getenv('TELEGRAM_CHAT_ID'),
        'timeout' => 10.0
    ]),
    new Definition('database.configs', [
        'driver' => 'pgsql',
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'database' => getenv('DB_DATABASE'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ]),
    new Definition('mcp.tools', [
        ['handler' => [PublishTool::class, 'publish'], 'name' => 'publish', 'description' => 'Publishes on the Telegram channel by post'],
        ['handler' => [PingTool::class, 'ping'], 'name' => 'ping', 'description' => 'Returns "pong: {message}". Useful for connectivity tests.']
    ])
]);