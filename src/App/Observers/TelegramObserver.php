<?php namespace App\Observers;

use RuntimeException;
use Throwable;

class TelegramObserver extends AbstractObserver
{
    public function update(mixed $payload = null): bool
    {
        try
        {
            if(!$this->callback->observer('notify', $payload)) throw new RuntimeException('Telegram notify failed'); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('TelegramObserver failed: '.(string) $e);
        }

        return false;
    }
}
