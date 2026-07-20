<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class SearchPostsDispatcher extends AbstractDispatcher implements ResultInterface
{
    private array $results = [];

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false; $this->results = TelegramPost::where('text', 'ILIKE', '%' . (string) $payload . '%')->latest()->limit(10)->get(['message_id', 'text'])->all(); return true;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
