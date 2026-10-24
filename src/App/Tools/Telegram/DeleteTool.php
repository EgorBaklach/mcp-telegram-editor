<?php namespace App\Tools\Telegram;

use InvalidArgumentException;
use App\Dispatchers\Telegram\DeleteDispatcher;

final class DeleteTool
{
    public function __construct(private DeleteDispatcher $dispatcher) {}

    public function delete(int $messageId): string
    {
        if(!$messageId) throw new InvalidArgumentException('$messageId must not be empty'); return $this->dispatcher->dispatch($messageId) ? 'success' : 'failed';
    }
}
