<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\EditDispatcher;

final class EditTool
{
    public function __construct(private EditDispatcher $dispatcher) {}

    public function edit(int $messageId, string $text): string
    {
        if(!$messageId)
        {
            throw new InvalidArgumentException('$messageId must not be empty');
        }

        if(!$text)
        {
            throw new InvalidArgumentException('$text must not be empty');
        }

        return $this->dispatcher->dispatch([
            'message_id' => $messageId,
            'text' => $text,
        ]) ? 'success' : 'failed';
    }
}
