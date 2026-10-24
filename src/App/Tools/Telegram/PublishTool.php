<?php namespace App\Tools\Telegram;

use InvalidArgumentException;
use App\Dispatchers\Telegram\PublishDispatcher;

final class PublishTool
{
    public function __construct(private PublishDispatcher $dispatcher) {}

    public function publish(string $post): string
    {
        if(!$post) throw new InvalidArgumentException('$post must not be empty'); return $this->dispatcher->dispatch($post) ? 'success' : 'failed';
    }
}
