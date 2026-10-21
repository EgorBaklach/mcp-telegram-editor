<?php namespace Magistrale\Dispatchers\Telegram;

use Magistrale\Dispatchers\ResultInterface;
use App\Models\TelegramPost;

class SearchPostsDispatcher extends AbstractDispatcher implements ResultInterface
{
    private array $results = [];

    public function dispatch(mixed $payload = null): bool
    {
        $this->results = TelegramPost::where('text', 'ILIKE', '%'.addcslashes((string) $payload, '%_\\').'%')->latest()->limit(10)->get(['message_id', 'text'])->all(); return true;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
