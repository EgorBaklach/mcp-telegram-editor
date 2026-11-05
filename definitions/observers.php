<?php

use App\Dispatchers\Source\AbstractDispatcher as SourceDispatcher;
use App\Observers\TelegramObserver;
use App\Tools\FL\NotifyOrderTool;
use App\Tools\HH\NotifyVacancyTool;
use App\Tools\Telegram\NotifyTool;
use Cli\Commands\SourceMonitorCommand;
use Magistrale\Clients\{FLClient, HHClient, OpenRouterClient};
use Magistrale\Dispatchers\Queue\AbstractDispatcher as QueueDispatcher;

return [
    QueueDispatcher::class => TelegramObserver::class,
    SourceDispatcher::class => TelegramObserver::class,
    SourceMonitorCommand::class => TelegramObserver::class,
    NotifyTool::class => TelegramObserver::class,
    NotifyOrderTool::class => TelegramObserver::class,
    NotifyVacancyTool::class => TelegramObserver::class,
    FLClient::class => TelegramObserver::class,
    HHClient::class => TelegramObserver::class,
    OpenRouterClient::class => TelegramObserver::class,
];
