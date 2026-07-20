<?php namespace Magistrale\Clients;

use GuzzleHttp\Client;
use League\Container\DefinitionContainerInterface;
use Psr\Http\Message\ResponseInterface;

class Telegram extends Client
{
    private array $config;

    public function __construct(private DefinitionContainerInterface $container)
    {
        $this->config = $this->container->get('telegram.configs'); parent::__construct($this->config);
    }

    public function sendMessage(string $text): ResponseInterface
    {
        return $this->post('sendMessage', [
            'json' => [
                'chat_id' => $this->config['chat_id'],
                'text' => $text,
                'parse_mode' => 'HTML',
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
                'text' => $payload['text'],
                'parse_mode' => 'HTML',
            ],
        ]);
    }
}