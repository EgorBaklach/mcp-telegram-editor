<?php namespace Framework\Inflectors;

use Framework\Contracts\Inflector\{InflectorAggregateInterface, InflectorInterface};
use League\Container\DefinitionContainerInterface;

abstract class InflectorAbstract implements InflectorInterface
{
    public function __construct(protected readonly InflectorAggregateInterface $aggregate){}

    public function container(): ?DefinitionContainerInterface
    {
        return $this->aggregate->getContainer();
    }
}
