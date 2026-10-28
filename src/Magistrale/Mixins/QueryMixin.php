<?php namespace Magistrale\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use Magistrale\Contracts\MixinInterface;

class QueryMixin implements MixinInterface
{
    public function bindings(): Closure
    {
        return function(array $values): array
        {
            /** @var Builder $this */ return $this->cleanBindings(array_merge(...array_map('array_values', $values)));
        };
    }

    public function ingest(): Closure
    {
        return function(array $values, string $column = 'id', ?Closure $callback = null): array
        {
            if(!$values) throw new InvalidArgumentException('$values must not be empty');

            /** @var Builder $this */ $sql = $this->grammar->compileInsertOrIgnore($this, $values).' returning '.$column;

            $ids = array_column($this->connection->select($sql, $this->bindings($values)), $column);

            return $callback ? array_map($callback, $ids) : $ids;
        };
    }
}
