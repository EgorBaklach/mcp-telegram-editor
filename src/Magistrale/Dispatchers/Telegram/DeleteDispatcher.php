<?php namespace Magistrale\Dispatchers\Telegram;

use Throwable;

class DeleteDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        try
        {
            if($this->client->deleteMessage((int) $payload)->getStatusCode() === 200)
            {
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
