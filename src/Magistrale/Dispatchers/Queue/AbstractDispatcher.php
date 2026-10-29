<?php namespace Magistrale\Dispatchers\Queue;

use League\Container\DefinitionContainerInterface;
use Magistrale\Contracts\{ConsoleLoggerInterface, DispatcherInterface, MacroableInterface, ObservableInterface, RedisInterface};
use Magistrale\Traits\{MacroableTrait, ObservableTrait};

abstract class AbstractDispatcher implements DispatcherInterface, MacroableInterface, ObservableInterface
{
    use MacroableTrait, ObservableTrait;

    public readonly array $config;

    public function __construct(public readonly DefinitionContainerInterface $container, public readonly RedisInterface $redis, public readonly ConsoleLoggerInterface $logger)
    {
        $this->config = $this->container->get('queue');
    }

    abstract public function dispatch(mixed $payload = null): bool;
}
