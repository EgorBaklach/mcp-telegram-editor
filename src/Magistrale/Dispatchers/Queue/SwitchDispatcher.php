<?php namespace Magistrale\Dispatchers\Queue;

class SwitchDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if($this->state())
        {
            $this->redis->command(['SET', $this->config['keys']['paused'], '1']);
            $this->logger->info('Очередь поставлена на паузу.');
        }
        else
        {
            $this->redis->unlock($this->config['keys']['paused']);
            $this->logger->info('Работа очереди возобновлена.');
        }

        return true;
    }
}
