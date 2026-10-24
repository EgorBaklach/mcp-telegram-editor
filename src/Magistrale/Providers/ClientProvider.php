<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Magistrale\Clients\AbstractClient;

final class ClientProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    private array $clients = [];

    public function provides(string $id): bool
    {
        return is_subclass_of($id, AbstractClient::class);
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
