<?php

use Cli\Commands\AbstractCommand;
use Magistrale\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use Magistrale\Dispatchers\Telegram\{AbstractDispatcher as TelegramDispatcher, DeleteDispatcher};
use Magistrale\Mixins\{CommandMixin, FlMixin, OpenRouterMixin, TelegramMixin};

return [
    AbstractCommand::class => CommandMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    DeleteDispatcher::class => TelegramMixin::class,
    OpenRouterDispatcher::class => OpenRouterMixin::class,
    FlDispatcher::class => FlMixin::class,
];
