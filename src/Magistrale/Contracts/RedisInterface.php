<?php namespace Magistrale\Contracts;

interface RedisInterface
{
    public function locked(string $key, ?int $ttl = null): bool;

    public function unlock(string $key): bool;

    public function command(array $args): mixed;
}
