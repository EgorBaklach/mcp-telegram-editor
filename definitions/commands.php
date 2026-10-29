<?php

use Cli\Commands\{HelloWorldCommand, MigrateCommand, QueueManagerCommand, SourceMonitorCommand};
use Magistrale\Dispatchers\Migration\{CreateDispatcher, DownDispatcher, UpDispatcher};
use Magistrale\Dispatchers\Queue\{ClearDispatcher, RemoveDispatcher, StatusDispatcher, SwitchDispatcher, WorkDispatcher};
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
    QueueManagerCommand::class => [
        'work' => [
            'option' => [null, InputOption::VALUE_NONE, 'Проверить расписания и обработать очередь задач'],
            'dispatcher' => WorkDispatcher::class,
            'lock' => 'lock:queue:work',
        ],
        'status' => [
            'option' => [null, InputOption::VALUE_NONE, 'Показать статус очереди, активную и ожидающие задачи'],
            'dispatcher' => StatusDispatcher::class,
            'lock' => 'lock:queue:status',
        ],
        'switch' => [
            'option' => [null, InputOption::VALUE_NONE, 'Переключить режим работы очереди (пауза / возобновление)'],
            'dispatcher' => SwitchDispatcher::class,
            'lock' => 'lock:queue:switch',
        ],
        'remove' => [
            'option' => [null, InputOption::VALUE_OPTIONAL, 'Удалить конкретную задачу из очереди (например, --remove=fl:agy)', false],
            'dispatcher' => RemoveDispatcher::class,
            'lock' => 'lock:queue:remove',
        ],
        'clear' => [
            'option' => [null, InputOption::VALUE_NONE, 'Полностью очистить очередь ожидающих задач'],
            'dispatcher' => ClearDispatcher::class,
            'lock' => 'lock:queue:clear',
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
