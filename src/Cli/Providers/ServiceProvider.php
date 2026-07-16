<?php namespace Cli\Providers;

use Framework\Contracts\Console\ConsoleInterface;
use Framework\Providers\ProviderAbstract;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class ServiceProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    private array $dependencies = [];
    private array $commands = [];

    protected array $provides = [ConsoleInterface::class];

    public function boot(): void
    {
        $this->dependencies = $this->container()->get('dependencies');
        $this->commands = $this->container()->get('commands');
    }

    public function register(): void
    {
        $this->container()->add(ConsoleInterface::class, function(): ConsoleInterface
        {
            $console = new $this->dependencies['console']($this->container()); foreach ($this->commands as $command) $console->add(new $command); return $console;
        });
    }
}