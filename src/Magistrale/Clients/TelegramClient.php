<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class TelegramClient extends AbstractClient
{
    public function sendMessage(string $text): ResponseInterface
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

    public function deleteMessage(int $messageId): ResponseInterface
    {
        return $this->post('deleteMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'message_id' => $messageId,
            ],
        ]);
    }

    public function editMessage(array $payload): ResponseInterface
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
