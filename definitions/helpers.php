<?php

use App\Dispatchers\Source\AbstractDispatcher as SourceDispatcher;
use Cli\Commands\AbstractCommand;
use Magistrale\Dispatchers\Migration\AbstractDispatcher as MigrationDispatcher;
use Magistrale\Helpers\{ArrayHelper, TemplateHelper};

return [
    AbstractCommand::class => [
        ArrayHelper::class,
    ],
    SourceDispatcher::class => [
        ArrayHelper::class,
    ],
    MigrationDispatcher::class => [
        TemplateHelper::class,
    ],
];
