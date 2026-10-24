<?php namespace Framework\Providers;

use Framework\Contracts\Provider\{AggregateProviderInterface, ProviderInterface};
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

abstract class AbstractProvider extends AbstractServiceProvider implements ProviderInterface
{
    protected array $provides = [];

    public function __construct(private readonly AggregateProviderInterface $aggregate){}

    public function container(): ?DefinitionContainerInterface
    {
        return $this->aggregate->getContainer();
    }

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides, true);
    }
}
