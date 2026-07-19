<?php namespace Magistrale\Dispatchers\Telegram;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\DispatcherInterface;

abstract class AbstractDispatcher implements DispatcherInterface
{
    public function __construct(protected readonly Capsule $capsule) {}

    abstract public function dispatch(mixed $payload = null): bool;
}
