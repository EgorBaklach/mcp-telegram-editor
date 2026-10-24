<?php namespace Framework\Contracts\Inflector;

use League\Container\Inflector\InflectorAggregateInterface as InflectorAggregateInterfaceLeague;

interface AggregateInflectorInterface extends InflectorAggregateInterfaceLeague
{
    public function resolve(string $type, mixed $inflector): object;
}
