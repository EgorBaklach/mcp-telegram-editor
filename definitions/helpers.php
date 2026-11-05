<?php

use App\Dispatchers\Source\AbstractDispatcher as SourceDispatcher;
use Cli\Commands\AbstractCommand;
use Magistrale\Helpers\ArrayHelper;

return [
    AbstractCommand::class => [
        ArrayHelper::class,
    ],
    SourceDispatcher::class => [
        ArrayHelper::class,
    ],
];
