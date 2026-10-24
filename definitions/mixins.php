<?php

use Cli\Commands\AbstractCommand;
use Magistrale\Contracts\RedisInterface;
use Magistrale\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use Magistrale\Dispatchers\Telegram\AbstractDispatcher as TelegramDispatcher;
use Magistrale\Mixins\{CommandMixin, FlMixin, OpenRouterMixin, RedisMixin, TelegramMixin};

return [
    AbstractCommand::class => CommandMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    OpenRouterDispatcher::class => OpenRouterMixin::class,
    FlDispatcher::class => FlMixin::class,
    RedisInterface::class => RedisMixin::class,
];
