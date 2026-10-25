<?php namespace App\Dispatchers\Telegram;

use App\Models\TelegramPost;

class DeleteDispatcher extends AbstractDispatcher
{
    protected const method = 'remove';

    public function dispatch(mixed $payload = null): bool
    {
        if(!parent::dispatch($payload)) return false; TelegramPost::where('message_id', $payload)->delete(); return true;
    }
}
