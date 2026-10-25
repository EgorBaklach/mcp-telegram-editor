<?php

use App\Dispatchers\Source\FlDispatcher;
use App\Operators\FlOperator;
use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Operators\MigrateOperator;

return [
    FlDispatcher::class => FlOperator::class,
    MigrationDispatcher::class => MigrateOperator::class,
];
