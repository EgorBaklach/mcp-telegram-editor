<?php namespace Magistrale\Dispatchers\Queue;

class ClearDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        $this->redis->unlock($this->config['keys']['pending']); return $this->logger->info('Очередь ожидания полностью очищена.');
    }
}
