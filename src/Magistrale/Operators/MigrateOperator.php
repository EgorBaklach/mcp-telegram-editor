<?php namespace Magistrale\Operators;

use Illuminate\Database\Eloquent\Builder;
use Laminas\Stdlib\Glob;
use Magistrale\Contracts\OperatorInterface;
use Magistrale\Dispatchers\Migration\AbstractDispatcher;
use Magistrale\Models\Migration;
use RuntimeException;
use Throwable;

class MigrateOperator implements OperatorInterface
{
    public function __invoke(AbstractDispatcher $dispatcher): array
    {
        return [
            'migrate.pending' => fn(): array => array_values(array_diff(Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true), Migration::query()->select('migration')->pluck('migration')->all())),
            'migrate.rollback' => fn($target = null) => fn(Builder $query) => match($target)
            {
                'all' => $query->get()->all(), null => $query->where('batch', Migration::query()->max('batch'))->get()->all() ?: [],

                default => is_string($target) && ctype_digit($target) ? $query->where('id', (int) $target)->get()->all() : []
            },
            'migrate.resolve' => function(string $method, string $file) use ($dispatcher): bool
            {
                try
                {
                    if(!file_exists($file)) throw new RuntimeException('Файл миграции '.$file.' не найден.');

                    $instance = require $file; $instance->{$method}(); $dispatcher->command->logger->info($method.' миграции '.$file.' успешно выполнен');

                    return true;
                }
                catch(Throwable $e)
                {
                    $dispatcher->command->logger->error('Ошибка при '.$method.' миграции '.$file.': '.(string) $e);
                }

                return false;
            }
        ];
    }
}
