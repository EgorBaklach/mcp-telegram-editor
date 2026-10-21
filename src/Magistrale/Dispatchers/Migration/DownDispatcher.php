<?php namespace Magistrale\Dispatchers\Migration;

use App\Models\Migration;
use Throwable;

class DownDispatcher extends AbstractDispatcher
{
    protected const method = 'down';

    public function dispatch(mixed $payload = null): bool
    {
        if(!$migrations = $this->getMigrationsToRollback($payload)) return $this->command->logger->info('Не найдено миграций для отката.');

        foreach($migrations as $row) if(!$this->rollbackMigration($row)) return false;

        return $this->command->logger->info('Откат миграций успешно завершен!');
    }

    private function getMigrationsToRollback(string|int|null $target): array
    {
        $query = Migration::query()->orderByDesc('id');

        return match(true)
        {
            $target === null => $query->where('batch', Migration::query()->max('batch'))->get()->all() ?: [],
            $target === 'all' => $query->get()->all(),
            ctype_digit((string) $target) => $query->where('id', (int) $target)->get()->all(),
            default => []
        };
    }

    private function rollbackMigration(object $row): bool
    {
        $this->command->logger->comment("Откат миграции {$row->migration} (ID: {$row->id})... ");

        if(!file_exists($row->migration))
        {
            return $this->command->logger->error("Файл миграции {$row->migration} не найден. Отмена операции.");
        }

        try
        {
            $this->runMigration($row->migration); Migration::query()->where('id', $row->id)->delete();

            return $this->command->logger->info("Миграция {$row->migration} успешно откачена.");
        }
        catch(Throwable $e)
        {
            return $this->command->logger->error("Ошибка при откате миграции {$row->migration}: {$e->getMessage()}");
        }
    }
}
