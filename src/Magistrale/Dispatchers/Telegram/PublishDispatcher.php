<?php namespace Magistrale\Dispatchers\Telegram;

use App\Models\TelegramPost;

class PublishDispatcher extends AbstractDispatcher
{
    protected const method = 'sendMessage';

    public function dispatch(mixed $payload = null): bool
    {
        if(!parent::dispatch($payload)) return false;

        if($messageId = $this->extractMessageId()) TelegramPost::create(['message_id' => $messageId, 'text' => (string) $payload]);

        return true;
    }
}
