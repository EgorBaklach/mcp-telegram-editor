<?php namespace Framework\Contracts\Inflector;

use League\Container\Inflector\InflectorAggregateInterface as InflectorAggregateInterfaceLeague;

interface InflectorAggregateInterface extends InflectorAggregateInterfaceLeague
{
    public function resolve(string $type, mixed $inflector): object;
}
