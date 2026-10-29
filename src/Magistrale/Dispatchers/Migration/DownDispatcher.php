<?php namespace Magistrale\Dispatchers\Migration;

use Magistrale\Models\Migration;

class DownDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$migrations = $this->callback->migrate('rollback', $payload)(Migration::query()->orderByDesc('id')))
        {
            $this->logger->info('Не найдено миграций для отката.'); return true;
        }

        foreach($migrations as $row)
        {
            $this->logger->comment('Откат миграции '.$row->migration.' (ID: '.$row->id.')... ');

            if($this->callback->migrate('resolve', 'down', $row->migration)) Migration::query()->where('id', $row->id)->delete(); else return false;
        }

        $this->logger->info('Откат миграций успешно завершен!'); return true;
    }
}
