<?php namespace Magistrale\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class EloquentMixin
{
    public function ingest(): Closure
    {
        return function(array $values, string $column = 'id', ?Closure $callback = null): array
        {
            /** @var Builder $this */ if($values) $values = $this->addTimestampsToUpsertValues(array_values($values));

            return $this->toBase()->ingest($values, $column, $callback);
        };
    }
}
