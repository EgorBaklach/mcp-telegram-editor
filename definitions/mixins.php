<?php

use App\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use App\Dispatchers\Telegram\{AbstractDispatcher as TelegramDispatcher, DeleteDispatcher};
use App\Mixins\{FlMixin, OpenRouterMixin, TelegramMixin};
use Cli\Commands\AbstractCommand;
use Magistrale\Mixins\CommandMixin;

return [
    AbstractCommand::class => CommandMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    DeleteDispatcher::class => TelegramMixin::class,
    OpenRouterDispatcher::class => OpenRouterMixin::class,
    FlDispatcher::class => FlMixin::class,
];
