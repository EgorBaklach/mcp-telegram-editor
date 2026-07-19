<?php namespace Magistrale\HTTPClients;

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
            ],
        ]);
    }
}