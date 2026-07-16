<?php namespace Framework\Contracts\Provider;

use League\Container\ContainerAwareInterface;
use League\Container\DefinitionContainerInterface;

interface ProviderAggregateInterface
{
    public function resolve(mixed $provider): ProviderInterface;
    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface;
    public function getContainer(): DefinitionContainerInterface;
}
