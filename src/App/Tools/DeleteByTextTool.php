<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\DeleteByTextDispatcher;

final class DeleteByTextTool
{
    public function __construct(private DeleteByTextDispatcher $dispatcher) {}

    public function deleteByText(string $text): string
    {
        if(!$text) throw new InvalidArgumentException('$text must not be empty'); return $this->dispatcher->dispatch($text) ? 'success' : 'failed';
    }
}
