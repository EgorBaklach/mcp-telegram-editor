<?php

use App\Dispatchers\Source\AbstractDispatcher as SourceDispatcher;
use App\Dispatchers\SourceMonitor\AbstractDispatcher as MonitorDispatcher;
use App\Observers\TelegramObserver;
use App\Tools\Telegram\NotifyTool;

return [
    SourceDispatcher::class => TelegramObserver::class,
    MonitorDispatcher::class => TelegramObserver::class,
    NotifyTool::class => TelegramObserver::class,
];
