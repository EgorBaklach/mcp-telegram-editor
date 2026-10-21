<?php

use Cli\Commands\{HelloWorldCommand, MigrateCommand, SourceMonitorCommand};
use Magistrale\Dispatchers\Migration\{CreateDispatcher, DownDispatcher, UpDispatcher};
use Magistrale\Dispatchers\SourceMonitor\OpenRouterDispatcher;

return [
    HelloWorldCommand::class => [],
    MigrateCommand::class => [
        'up' => UpDispatcher::class,
        'down' => DownDispatcher::class,
        'new' => CreateDispatcher::class,
    ],
    SourceMonitorCommand::class => [
        'openrouter' => OpenRouterDispatcher::class,
    ],
];
