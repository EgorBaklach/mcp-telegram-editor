<?php

use Cli\Providers\ServiceProvider as CliServiceProvider;
use Framework\Providers\{ProviderAggregate, ServiceProvider};
use Magistrale\Providers\{CallbackProvider, ClientProvider, DatabaseServiceProvider, McpServiceProvider, RedisProvider};

return new ProviderAggregate([
    CliServiceProvider::class,
    ServiceProvider::class,
    CallbackProvider::class,
    ClientProvider::class,
    RedisProvider::class,
    McpServiceProvider::class,
    DatabaseServiceProvider::class,
]);
