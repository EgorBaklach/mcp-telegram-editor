<?php

use App\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use App\Operators\{FlOperator, OpenRouterOperator};
use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Operators\MigrateOperator;

return [
    FlDispatcher::class => FlOperator::class,
    OpenRouterDispatcher::class => OpenRouterOperator::class,
    MigrationDispatcher::class => MigrateOperator::class,
];
