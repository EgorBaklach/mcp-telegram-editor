<?php namespace Magistrale\Mixins;

use Closure;
use Magistrale\Dispatchers\Telegram\AbstractDispatcher;

class TelegramMixin
{
    public function escapeLike(): Closure
    {
        return function(string $text): string
        {
            return '%'.addcslashes($text, '%_\\').'%';
        };
    }

    public function decodeResponse(): Closure
    {
        return function(): array
        {
            /** @var AbstractDispatcher $this */ return json_decode((string) $this->response?->getBody(), true, 512, JSON_BIGINT_AS_STRING) ?: [];
        };
    }
}
