<?php namespace App\Mixins;

use App\Dispatchers\Telegram\AbstractDispatcher;
use Closure;
use Throwable;

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

    public function prepare(): Closure
    {
        return function(mixed $payload = null): string
        {
            return $payload instanceof Throwable
                ? '<b>⚠️ '.$payload::class.' ['.basename($payload->getFile()).':'.$payload->getLine().']</b>'.PHP_EOL.'<pre>'.htmlspecialchars($payload->getMessage(), ENT_QUOTES | ENT_HTML5, 'UTF-8').'</pre>'
                : (string) $payload;
        };
    }
}
