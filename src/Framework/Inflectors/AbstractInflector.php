<?php namespace Framework\Inflectors;

use Framework\Contracts\Inflector\{AggregateInflectorInterface, InflectorInterface};
use League\Container\DefinitionContainerInterface;

abstract class AbstractInflector implements InflectorInterface
{
    public function __construct(protected readonly AggregateInflectorInterface $aggregate){}

    public function container(): ?DefinitionContainerInterface
    {
        return $this->aggregate->getContainer();
    }
}
