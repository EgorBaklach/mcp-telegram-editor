<?php

use App\Dispatchers\Source\{FLDispatcher, HHDispatcher};
use App\Operators\{FLOperator, HHOperator};
use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Operators\MigrateOperator;

return [
    FLDispatcher::class => FLOperator::class,
    HHDispatcher::class => HHOperator::class,
    MigrationDispatcher::class => MigrateOperator::class,
];
