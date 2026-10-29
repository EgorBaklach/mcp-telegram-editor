<?php namespace Magistrale\Dispatchers\Queue;

use League\Container\DefinitionContainerInterface;
use Magistrale\Contracts\{ConsoleLoggerInterface, DispatcherInterface, MacroableInterface, RedisInterface};
use Magistrale\Traits\MacroableTrait;

abstract class AbstractDispatcher implements DispatcherInterface, MacroableInterface
{
    use MacroableTrait;

    public readonly array $config;

    public function __construct(public readonly DefinitionContainerInterface $container, public readonly RedisInterface $redis, public readonly ConsoleLoggerInterface $logger)
    {
        $this->config = $this->container->get('queue');
    }

    abstract public function dispatch(mixed $payload = null): bool;
}
