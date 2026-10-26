<?php namespace App\Tools\Telegram;

use InvalidArgumentException;
use Magistrale\Contracts\ObservableInterface;
use Magistrale\Traits\ObservableTrait;

final class NotifyTool implements ObservableInterface
{
    use ObservableTrait;

    public function notify(string $message): string
    {
        if(!$message) throw new InvalidArgumentException('$message must not be empty'); return $this->observer->update($message) ? 'success' : 'failed';
    }
}
