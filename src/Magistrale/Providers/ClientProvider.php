<?php namespace Magistrale\Providers;

use Framework\Providers\AbstractProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Magistrale\Contracts\ClientInterface;

final class ClientProvider extends AbstractProvider implements BootableServiceProviderInterface
{
    private array $clients = [];

    public function provides(string $id): bool
    {
        return is_subclass_of($id, ClientInterface::class);
    }

    public function boot(): void
    {
        $this->clients = $this->container()->get('clients');
    }

    public function register(): void
    {
        foreach($this->clients as $client => $config) $this->container()->add($client)->addArgument($config);
    }
}
