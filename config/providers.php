<?php

use Cli\Providers\ServiceProvider as CliServiceProvider;
use Magistrale\Providers\McpServiceProvider;
use Framework\Providers\ProviderAggregate;
use Framework\Providers\ServiceProvider;

return new ProviderAggregate([
    CliServiceProvider::class,
    ServiceProvider::class,
    McpServiceProvider::class
]);