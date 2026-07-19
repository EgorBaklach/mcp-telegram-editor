<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class DeleteByTextDispatcher extends AbstractDispatcher
{
    protected const method = 'deleteMessage';

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        $post = TelegramPost::where('text', 'LIKE', '%' . (string) $payload . '%')->latest()->first();
        if(!$post)
        {
            $this->logger->warning("Post with text '{$payload}' not found in database."); return false;
        }

        if(!parent::dispatch($post->message_id)) return false;

        $post->delete();
        return true;
    }
}
