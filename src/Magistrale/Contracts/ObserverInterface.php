<?php namespace Magistrale\Contracts;

interface ObserverInterface
{
    public function update(mixed $payload = null): bool;
}
