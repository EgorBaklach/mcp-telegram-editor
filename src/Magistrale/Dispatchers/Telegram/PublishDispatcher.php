<?php namespace Magistrale\Dispatchers\Telegram;

use Throwable;

use App\Models\TelegramPost;

class PublishDispatcher extends AbstractDispatcher
{
    protected const method = 'sendMessage';

    public function dispatch(mixed $payload = null): bool
    {
        if(!parent::dispatch($payload)) return false;

        $data = json_decode((string) $this->lastResponse->getBody(), true);
        if(isset($data['ok'], $data['result']['message_id']))
        {
            TelegramPost::create([
                'message_id' => $data['result']['message_id'],
                'text' => (string) $payload
            ]);
        }

        return true;
    }
}