<?php namespace Framework\Contracts\Inflector;

use Psr\Container\ContainerInterface;

interface InflectorInterface
{
    public function container(): ?ContainerInterface;
}
