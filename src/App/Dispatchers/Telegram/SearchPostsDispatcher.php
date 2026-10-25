<?php namespace App\Dispatchers\Telegram;

use App\Models\TelegramPost;
use Magistrale\Contracts\ResultInterface;

class SearchPostsDispatcher extends AbstractDispatcher implements ResultInterface
{
    private array $results = [];

    public function dispatch(mixed $payload = null): bool
    {
        $this->results = TelegramPost::where('text', 'ILIKE', $this->escape($payload))->latest()->limit(10)->get(['message_id', 'text'])->all(); return true;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
