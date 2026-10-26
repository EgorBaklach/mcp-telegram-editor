<?php namespace App\Mixins;

use App\Dispatchers\Telegram\AbstractDispatcher;
use Closure;

class TelegramMixin
{
    public function escape(): Closure
    {
        return function(string $text): string
        {
            return '%'.addcslashes($text, '%_\\').'%';
        };
    }

    public function decode(): Closure
    {
        return function(): array
        {
            /** @var AbstractDispatcher $this */ return json_decode($this->response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
        };
    }

    public function messageId(): Closure
    {
        return function(): int
        {
            /** @var AbstractDispatcher $this */ $result = $this->decode(); return (int) $result['result']['message_id'];
        };
    }
}
