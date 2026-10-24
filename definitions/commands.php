<?php

use App\Dispatchers\SourceMonitor\OpenRouterDispatcher;
use Cli\Commands\{HelloWorldCommand, MigrateCommand, SourceMonitorCommand};
use Magistrale\Dispatchers\Migration\{CreateDispatcher, DownDispatcher, UpDispatcher};

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
