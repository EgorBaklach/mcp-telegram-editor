<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class DeleteByTextDispatcher extends DeleteDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$post = TelegramPost::where('text', 'ILIKE', '%'.addcslashes((string) $payload, '%_\\').'%')->latest()->first())
        {
            $this->logger->warning("Post with text '{$payload}' not found in database."); return false;
        }

        return parent::dispatch($post->message_id);
    }
}
