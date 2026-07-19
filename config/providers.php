<?php

use Cli\Providers\ServiceProvider as CliServiceProvider;
use Magistrale\Providers\{McpServiceProvider, DatabaseServiceProvider, MigrationServiceProvider};
use Framework\Providers\{ProviderAggregate, ServiceProvider};

return new ProviderAggregate([
    CliServiceProvider::class,
    ServiceProvider::class,
    McpServiceProvider::class,
    DatabaseServiceProvider::class,
    MigrationServiceProvider::class
]);