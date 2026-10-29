<?php namespace Magistrale\Dispatchers\Queue;

class StatusDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        $this->logger->info('Статус очереди: '.($this->state() ? 'Активна' : 'На паузе'));

        $this->logger->comment('Выполняется сейчас: '.($this->redis->command(['GET', $this->config['keys']['active']]) ?: 'нет'));

        if(!$pending = $this->pending()) return $this->logger->info('Очередь ожидания пуста.');

        $this->logger->info('Ожидают в очереди ('.count($pending).'):');

        foreach($pending as $index => $job) $this->logger->comment('  '.($index + 1).'. '.$job);

        return true;
    }
}
