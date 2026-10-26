<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class TelegramClient extends AbstractClient
{
    public function publish(string $text): ?ResponseInterface
    {
        return $this->resolve('post', 'sendRichMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'rich_message' => [
                    'html' => $text,
                ],
            ],
        ]);
    }

    public function remove(int $id): ?ResponseInterface
    {
        return $this->resolve('post', 'deleteMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'message_id' => $id,
            ],
        ]);
    }

    public function edit(array $payload): ?ResponseInterface
    {
        return $this->resolve('post', 'editMessageText', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'message_id' => $payload['message_id'],
                'rich_message' => [
                    'html' => $payload['text'],
                ],
            ],
        ]);
    }

    public function notify(string $text): ?ResponseInterface
    {
        return $this->resolve('post', 'sendRichMessage', [
            'json' => [
                'chat_id' => $this->config['admin_id'],
                'rich_message' => [
                    'html' => $text,
                ],
            ],
        ]);
    }
}
