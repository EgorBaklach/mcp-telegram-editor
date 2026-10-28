<?php

use App\Dispatchers\Source\{FLDispatcher, HHDispatcher};
use App\Dispatchers\Telegram\{AbstractDispatcher as TelegramDispatcher, DeleteDispatcher};
use App\Mixins\{FLMixin, HHMixin, TelegramMixin};
use App\Observers\AbstractObserver;
use Cli\Commands\AbstractCommand;
use Magistrale\Mixins\CommandMixin;

return [
    AbstractCommand::class => CommandMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    DeleteDispatcher::class => TelegramMixin::class,
    AbstractObserver::class => TelegramMixin::class,
    FLDispatcher::class => FLMixin::class,
    HHDispatcher::class => HHMixin::class,
];
