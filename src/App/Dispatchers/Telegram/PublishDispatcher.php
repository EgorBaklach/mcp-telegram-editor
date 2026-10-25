<?php namespace App\Dispatchers\Telegram;

use App\Models\TelegramPost;

class PublishDispatcher extends AbstractDispatcher
{
    protected const method = 'publish';

    public function dispatch(mixed $payload = null): bool
    {
        if(!parent::dispatch($payload)) return false;

        TelegramPost::create(['message_id' => $this->messageId(), 'text' => (string) $payload]);

        return true;
    }
}
