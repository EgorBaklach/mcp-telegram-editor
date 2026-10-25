<?php namespace Magistrale\Dispatchers\Migration;

use Magistrale\Models\Migration;

class UpDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$files = $this->callback->migrate('pending'))
        {
            $this->command->logger->info('База данных в актуальном состоянии. Нет новых миграций.'); return true;
        }

        $batch = (Migration::query()->max('batch') ?? 0) + 1;

        foreach($files as $migration)
        {
            $this->command->logger->comment('Применение миграции '.$migration.'...');

            if($this->callback->migrate('resolve', 'up', $migration)) Migration::query()->create(compact('migration', 'batch')); else return false;
        }

        $this->command->logger->info('Все новые миграции успешно применены!'); return true;
    }
}
