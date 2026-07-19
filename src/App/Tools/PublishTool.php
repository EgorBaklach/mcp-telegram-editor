<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\PublishDispatcher;
use Symfony\Component\Process\Process;

final class PublishTool
{
    public function __construct(private PublishDispatcher $dispatcher) {}

    public function publish(string $post): string
    {
        if(!$post) throw new InvalidArgumentException('$post must not be empty'); return $this->dispatcher->dispatch($post) ? 'success' : 'failed';
    }
}