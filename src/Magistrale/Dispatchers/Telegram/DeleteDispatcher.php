<?php namespace Magistrale\Dispatchers\Telegram;

use Throwable;

class DeleteDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        try
        {
            $response = $this->client->deleteMessage((int) $payload);

            if($response->getStatusCode() === 200)
            {
                $this->logResult($response);
                $this->logger->info("Message {$payload} deleted successfully."); return true;
            }
        }
        catch(Throwable $e)
        {
            $this->logger->error("Failed to delete message: {$e->getMessage()}");
        }

        return false;
    }
}
