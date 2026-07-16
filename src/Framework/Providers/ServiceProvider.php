<?php namespace Framework\Providers;

use Framework\Contracts\Emitter\EmitterInterface;
use Framework\Contracts\Router\RouterInterface;
use Laminas\Diactoros\ResponseFactory;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Route\Strategy\StrategyInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class ServiceProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    private array $dependencies = [];

    protected array $provides = [
        ResponseFactoryInterface::class,
        StrategyInterface::class,
        RouterInterface::class,
        EmitterInterface::class
    ];

    public function boot(): void
    {
        $this->dependencies = $this->container()->get('dependencies');
    }

    public function register(): void
    {
        $this->container()->add(ResponseFactoryInterface::class, ResponseFactory::class);
        $this->container()->add(StrategyInterface::class, $this->dependencies['strategy'])->addMethodCall('setContainer', [$this->container()]);
        $this->container()->add(RouterInterface::class, $this->dependencies['router'])->addMethodCall('setStrategy', [StrategyInterface::class]);
        $this->container()->add(EmitterInterface::class, $this->dependencies['emitter'])->addMethodCall('emit', [RouterInterface::class]);
    }
}