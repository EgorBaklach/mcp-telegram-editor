<?php namespace Magistrale\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentMixin
{
    public function ingest(): Closure
    {
        return function(array $values, string $column = 'id', ?Closure $callback = null): array
        {
            /** @var Builder $this */ return $this->toBase()->ingest($this->addTimestampsToUpsertValues(array_values($values)), $column, $callback);
        };
    }
}
