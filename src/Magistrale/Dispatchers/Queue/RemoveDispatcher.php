<?php namespace Magistrale\Dispatchers\Queue;

class RemoveDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!is_string($payload) || !trim($payload)) return $this->logger->error('Укажите задачу для удаления (например, --remove=fl:agy).');

        $removed = $this->redis->command(['LREM', $this->config['keys']['pending'], '0', $payload]) > 0;

        return $removed ? $this->logger->info('Задача '.$payload.' удалена из очереди.') : $this->logger->comment('Задача '.$payload.' не найдена в очереди.');
    }
}
