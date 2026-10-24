<?php namespace App\Dispatchers\Telegram;

use App\Models\TelegramPost;

class EditDispatcher extends AbstractDispatcher
{
    protected const method = 'editMessage';

    public function dispatch(mixed $payload = null): bool
    {
        if(!parent::dispatch($payload)) return false; TelegramPost::updateOrCreate(['message_id' => (int) $payload['message_id']], ['text' => (string) $payload['text']]); return true;
    }
}
