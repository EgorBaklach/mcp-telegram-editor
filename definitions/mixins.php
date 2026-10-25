<?php

use App\Dispatchers\Source\FlDispatcher;
use App\Dispatchers\Telegram\{AbstractDispatcher as TelegramDispatcher, DeleteDispatcher};
use App\Mixins\{FlMixin, TelegramMixin};
use Cli\Commands\AbstractCommand;
use Magistrale\Mixins\CommandMixin;

return [
    AbstractCommand::class => CommandMixin::class,
    TelegramDispatcher::class => TelegramMixin::class,
    DeleteDispatcher::class => TelegramMixin::class,
    FlDispatcher::class => FlMixin::class,
];
