<?php

use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use Magistrale\Operators\{FlOperator, MigrateOperator, OpenRouterOperator};

return [
    FlDispatcher::class => FlOperator::class,
    OpenRouterDispatcher::class => OpenRouterOperator::class,
    MigrationDispatcher::class => MigrateOperator::class,
];
