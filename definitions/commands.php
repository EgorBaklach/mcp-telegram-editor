<?php

use Cli\Commands\{HelloWorldCommand, MigrateCommand, SourceMonitorCommand};
use Magistrale\Dispatchers\Migration\{CreateDispatcher, DownDispatcher, UpDispatcher};
use Symfony\Component\Console\Input\InputOption;

return [
    HelloWorldCommand::class => [],
    MigrateCommand::class => [
        'up' => [
            'option' => [null, InputOption::VALUE_NONE, 'Применить все ожидающие миграции (накатить)'],
            'dispatcher' => UpDispatcher::class,
            'lock' => 'lock:migrate:up',
        ],
        'down' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Откат миграций (последний батч, all для всех, либо ID конкретной миграции)', false],
            'dispatcher' => DownDispatcher::class,
            'lock' => 'lock:migrate:down',
        ],
        'new' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Создать новый файл миграции с указанным именем', false],
            'dispatcher' => CreateDispatcher::class,
            'lock' => 'lock:migrate:new',
        ],
    ],
    SourceMonitorCommand::class => [
        'openrouter' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг бесплатных AI-моделей OpenRouter через указанного агента (например, --openrouter=agy)', false],
            'lock' => 'lock:source:openrouter',
        ],
        'fl' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг новых заказов FL.ru через указанного агента (например, --fl=agy)', false],
            'lock' => 'lock:source:fl',
        ],
        'hh' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг новых вакансий HH.ru через указанного агента (например, --hh=agy)', false],
            'lock' => 'lock:source:hh',
        ],
    ],
];
