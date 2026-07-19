<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\DeleteDispatcher;

final class DeleteTool
{
    public function __construct(private DeleteDispatcher $dispatcher) {}

    public function delete(int $messageId): string
    {
        if(!$messageId) throw new InvalidArgumentException('$messageId must not be empty');

        if($this->dispatcher->dispatch($messageId)) return 'success';

        return 'failed';
    }
}
