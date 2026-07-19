<?php namespace Magistrale\Dispatchers\Telegram;

class PublishDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        return true;
    }
}