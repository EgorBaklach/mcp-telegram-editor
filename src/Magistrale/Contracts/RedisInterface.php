<?php namespace Magistrale\Contracts;

interface RedisInterface
{
    public function isLocked(string $key, ?int $ttl = null): bool;

    public function releaseLock(string $key): bool;

    public function command(array $args): mixed;
}
