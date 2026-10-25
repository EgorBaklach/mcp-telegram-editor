<?php namespace App\Tools\Telegram;

use InvalidArgumentException;
use App\Dispatchers\Telegram\DeleteByTextDispatcher;

final class DeleteByTextTool
{
    public function __construct(private DeleteByTextDispatcher $dispatcher) {}

    public function delete(string $text): string
    {
        if(!$text) throw new InvalidArgumentException('$text must not be empty'); return $this->dispatcher->dispatch($text) ? 'success' : 'failed';
    }
}
