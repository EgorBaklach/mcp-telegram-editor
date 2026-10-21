<?php namespace Magistrale\Mixins;

use Closure;
use Magistrale\Dispatchers\Migration\AbstractDispatcher;
use RuntimeException;

class MigrationMixin
{
    public function formatMigrationName(): Closure
    {
        return function(string $name): string
        {
            return date('Y_m_d_His').'_'.preg_replace('/[^a-z0-9_]/', '_', strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name))).'.php';
        };
    }

    public function runMigration(): Closure
    {
        return function(string $file): void
        {
            /** @var AbstractDispatcher $this */ $method = static::method; $instance = require $file;

            if(!is_object($instance) || !method_exists($instance, $method)) throw new RuntimeException("Файл миграции {$file} должен возвращать анонимный класс с методом {$method}()");

            $instance->{$method}();
        };
    }
}
