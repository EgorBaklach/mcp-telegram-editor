<?php namespace Magistrale\Dispatchers;

interface DispatcherInterface
{
    /**
    * Запускает выполнение логики диспетчера.
    *
    * @param mixed $payload
    * @return bool
    */
    public function dispatch(mixed $payload = null): bool;
}