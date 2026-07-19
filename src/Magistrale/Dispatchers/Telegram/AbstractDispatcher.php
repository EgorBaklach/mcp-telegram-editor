<?php namespace Magistrale\Dispatchers\Telegram;

use Illuminate\Database\Capsule\Manager as Capsule;
use League\Container\DefinitionContainerInterface;
use Magistrale\Dispatchers\DispatcherInterface;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected readonly Capsule $capsule;

    public function __construct(protected readonly DefinitionContainerInterface $container)
    {
        $this->capsule = $this->container->get(Capsule::class);
    }

    abstract public function dispatch(mixed $payload = null): bool;
}
