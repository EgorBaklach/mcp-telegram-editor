<?php namespace Magistrale\Dispatchers\Telegram;

use Throwable;

class PublishDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        try
        {
            if($this->client->sendMessage((string) $payload)->getStatusCode() === 200)
            {
                $this->logger->info("Message published to Telegram channel successfully."); return true;
            }
        }
        catch(Throwable $e)
        {
            $this->logger->error("Failed to publish message to Telegram: {$e->getMessage()}");
        }

        return false;
    }
}