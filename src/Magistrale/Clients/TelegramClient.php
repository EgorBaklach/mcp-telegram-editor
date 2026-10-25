<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class TelegramClient extends AbstractClient
{
    public function publish(string $text): ResponseInterface
    {
        return $this->post('sendRichMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'rich_message' => [
                    'html' => $text,
                ],
            ],
        ]);
    }

    public function remove(int $id): ResponseInterface
    {
        return $this->post('deleteMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'message_id' => $id,
            ],
        ]);
    }

    public function edit(array $payload): ResponseInterface
    {
        return $this->post('editMessageText', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'message_id' => $payload['message_id'],
                'rich_message' => [
                    'html' => $payload['text'],
                ],
            ],
        ]);
    }
}
