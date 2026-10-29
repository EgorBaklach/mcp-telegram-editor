<?php

use App\Dispatchers\Source\{FLDispatcher, HHDispatcher};
use App\Dispatchers\Telegram\{AbstractDispatcher as TelegramDispatcher, DeleteDispatcher};
use App\Mixins\{FLMixin, HHMixin, TelegramMixin};
use App\Observers\AbstractObserver;
use Cli\Commands\AbstractCommand;
use Magistrale\Dispatchers\Queue\AbstractDispatcher as QueueDispatcher;
use Magistrale\Mixins\{CommandMixin, QueueMixin};

return [
    AbstractCommand::class => CommandMixin::class,
    QueueDispatcher::class => QueueMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    DeleteDispatcher::class => TelegramMixin::class,
    AbstractObserver::class => TelegramMixin::class,
    FLDispatcher::class => FLMixin::class,
    HHDispatcher::class => HHMixin::class,
];
