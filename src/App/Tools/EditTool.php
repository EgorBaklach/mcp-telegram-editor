<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\EditDispatcher;

final class EditTool
{
    public function __construct(private EditDispatcher $dispatcher) {}

    public function edit(int $messageId, string $text): string
    {
        foreach(compact('messageId', 'text') as $key => $value) if(!$value) throw new InvalidArgumentException("{$key} must not be empty");

        return $this->dispatcher->dispatch(['message_id' => $messageId, 'text' => $text]) ? 'success' : 'failed';
    }
}
