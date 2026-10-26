<?php

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Magistrale\Mixins\{EloquentMixin, QueryMixin};

return [
    'access' => [
        'driver' => 'pgsql',
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'database' => getenv('DB_DATABASE'),
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],
    'mixins' => [
        QueryBuilder::class => QueryMixin::class,
        EloquentBuilder::class => EloquentMixin::class,
    ],
];
