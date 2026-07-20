<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class EditDispatcher extends AbstractDispatcher
{
    protected const method = 'editMessage';

    public function dispatch(mixed $payload = null): bool
    {
        if(!is_array($payload) || empty($payload['message_id']) || !isset($payload['text']))
        {
            return false;
        }

        if(!parent::dispatch($payload))
        {
            return false;
        }

        if($post = TelegramPost::where('message_id', (int) $payload['message_id'])->first())
        {
            $post->update(['text' => (string) $payload['text']]);
        }

        return true;
    }
}
