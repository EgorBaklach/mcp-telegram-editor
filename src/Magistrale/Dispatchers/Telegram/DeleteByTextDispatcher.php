<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class DeleteByTextDispatcher extends DeleteDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        if(!$post = TelegramPost::where('text', 'ILIKE', '%' . (string) $payload . '%')->latest()->first())
        {
            $this->logger->warning("Post with text '{$payload}' not found in database."); return false;
        }

        return parent::dispatch($post->message_id);
    }
}
