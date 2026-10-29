<?php namespace Magistrale\Dispatchers\Migration;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Contracts\{ConsoleLoggerInterface, DispatcherInterface, OperableInterface};
use Magistrale\Traits\OperableTrait;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractDispatcher implements DispatcherInterface, OperableInterface
{
    use OperableTrait;

    public function __construct(public readonly Capsule $capsule, protected readonly Filesystem $fs, public readonly ConsoleLoggerInterface $logger) {}

    abstract public function dispatch(mixed $payload = null): bool;
}
