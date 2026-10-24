<?php namespace Framework\Providers;

use Framework\Contracts\Provider\{AggregateProviderInterface, ProviderInterface};
use League\Container\ContainerAwareTrait;
use League\Container\Exception\ContainerException;
use League\Container\ServiceProvider\{BootableServiceProviderInterface, ServiceProviderAggregateInterface};
use Traversable;

class AggregateProvider implements AggregateProviderInterface, ServiceProviderAggregateInterface
{
    use ContainerAwareTrait;

    private array $providers = [];

    private array $registered = [];

    public function __construct(array $providers)
    {
        foreach($providers as $provider) $this->providers[] = $this->resolve($provider);
    }

    public function resolve(mixed $provider): ProviderInterface
    {
        return $provider instanceof AbstractProvider ? $provider : new $provider($this);
    }

    public function add(mixed $provider): ServiceProviderAggregateInterface
    {
        $this->providers[] = $this->resolve($provider); return $this;
    }

    public function provides(string $id): bool
    {
        foreach($this->getIterator() as $provider) /** @var ProviderInterface $provider */ if($provider->provides($id)) return true; return false;
    }

    public function register(string $service): void
    {
        if(false === $this->provides($service)) throw new ContainerException(sprintf('(%s) is not provided by a service provider', $service));

        foreach($this->getIterator() as $provider)
        {
            /** @var ProviderInterface $provider */ $id = $provider->getIdentifier(); if(in_array($id, $this->registered, true) || !$provider->provides($service)) continue;

            if($provider instanceof BootableServiceProviderInterface) $provider->boot(); $provider->register(); $this->registered[] = $id;
        }
    }

    public function getIterator(): Traversable
    {
        yield from $this->providers;
    }
}
