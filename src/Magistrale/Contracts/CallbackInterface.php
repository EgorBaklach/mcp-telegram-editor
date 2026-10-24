<?php namespace Magistrale\Contracts;

use Closure;

/**
 * @property Closure[] $container
 */
interface CallbackInterface
{
    public function __get(string $name): Closure;

    public function __call(string $operator, array $arguments): mixed;
}
