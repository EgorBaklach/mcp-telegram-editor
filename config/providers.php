<?php

use Cli\Providers\ServiceProvider as CliServiceProvider;
use Framework\Providers\{AggregateProvider, ServiceProvider};
use Magistrale\Providers\{CallbackProvider, ClientProvider, DatabaseServiceProvider, McpServiceProvider, RedisProvider, TemplateProvider};

return new AggregateProvider([
    CliServiceProvider::class,
    ServiceProvider::class,
    TemplateProvider::class,
    CallbackProvider::class,
    ClientProvider::class,
    RedisProvider::class,
    McpServiceProvider::class,
    DatabaseServiceProvider::class,
]);
