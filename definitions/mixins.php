<?php

use Cli\Commands\AbstractCommand;
use Magistrale\Clients\Redis;
use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Dispatchers\OpenRouter\AbstractDispatcher as OpenRouterDispatcher;
use Magistrale\Dispatchers\Telegram\AbstractDispatcher as TelegramDispatcher;
use Magistrale\Mixins\{CommandMixin, MigrationMixin, OpenRouterMixin, RedisMixin, TelegramMixin};

return [
    AbstractCommand::class => CommandMixin::class,
    MigrationDispatcher::class => MigrationMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    OpenRouterDispatcher::class => OpenRouterMixin::class,
    Redis::class => RedisMixin::class,
];
