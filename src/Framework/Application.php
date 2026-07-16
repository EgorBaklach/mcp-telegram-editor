<?php namespace Framework;

use Framework\Contracts\Console\ConsoleInterface;
use Framework\Contracts\Emitter\EmitterInterface;
use League\Container\Container;
use League\Container\Definition\DefinitionAggregateInterface;
use League\Container\Inflector\InflectorAggregateInterface;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\ServiceProviderAggregateInterface;

class Application
{
    private readonly Container $container;

    public function __construct(DefinitionAggregateInterface $definitions, InflectorAggregateInterface $inflectors, ServiceProviderAggregateInterface $providers)
    {
        $this->container = new Container($definitions, $providers, $inflectors); $this->container->defaultToShared(); $this->container->delegate(new ReflectionContainer(true));
    }

    public static function make(array $config): self
    {
        return new self(...$config);
    }

    public function run(): void
    {
        $this->container->get(EmitterInterface::class);
    }

    public function cli(): void
    {
        $this->container->get(ConsoleInterface::class)->run();
    }
}
