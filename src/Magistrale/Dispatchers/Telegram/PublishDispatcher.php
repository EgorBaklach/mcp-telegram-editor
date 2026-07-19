<?php namespace Magistrale\Dispatchers\Telegram;

use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Log\LoggerInterface;
use Throwable;

class PublishDispatcher extends AbstractDispatcher
{
    public function __construct(
        Capsule $capsule,
        private readonly LoggerInterface $logger,
        private readonly Client $client
    ) {
        parent::__construct($capsule);
    }

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        $chatId = getenv('TELEGRAM_CHAT_ID');

        if(!$chatId)
        {
            $this->logger->error('Telegram chat ID is not configured in environment variables.'); return false;
        }

        try
        {
            $response = $this->client->post("sendMessage", [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => (string)$payload
                ]
            ]);

            if($response->getStatusCode() === 200)
            {
                $this->logger->info("Message published to Telegram channel {$chatId} successfully."); return true;
            }
        }
        catch(Throwable $e)
        {
            $this->logger->error("Failed to publish message to Telegram: {$e->getMessage()}");
        }

        return false;
    }
}