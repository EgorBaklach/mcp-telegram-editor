<?php namespace Framework\Providers;

use Framework\Contracts\Provider\{ProviderAggregateInterface, ProviderInterface};
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\{AbstractServiceProvider, ServiceProviderAggregateInterface};

abstract class ProviderAbstract extends AbstractServiceProvider implements ProviderInterface
{
    protected array $provides = [];

    public function __construct(private readonly ProviderAggregateInterface $aggregate){}

    public function container(): ?DefinitionContainerInterface
    {
        return $this->aggregate->getContainer();
    }

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }
}
