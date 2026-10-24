<?php namespace Framework\Contracts\Provider;

use League\Container\{ContainerAwareInterface, DefinitionContainerInterface};

interface AggregateProviderInterface
{
    public function resolve(mixed $provider): ProviderInterface;
    public function setContainer(DefinitionContainerInterface $container): ContainerAwareInterface;
    public function getContainer(): DefinitionContainerInterface;
}
