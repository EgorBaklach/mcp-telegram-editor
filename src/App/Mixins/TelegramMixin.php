<?php namespace App\Mixins;

use App\Dispatchers\Telegram\AbstractDispatcher;
use App\Observers\AbstractObserver;
use Closure;
use Magistrale\Contracts\MixinInterface;
use Throwable;

class TelegramMixin implements MixinInterface
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
        return function(mixed $payload = null, ?array $data = null): string
        {
            /** @var AbstractObserver $this */

            if($payload instanceof Throwable)
            {
                return $this->template->render('exception', [
                    'message' => $payload->getMessage(),
                    'e' => $payload
                ]);
            }

            return $data ? $this->template->render($payload, $data) : (string) $payload;
        };
    }
}
