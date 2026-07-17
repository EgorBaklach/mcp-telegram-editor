<?php namespace Magistrale\Dispatchers\Migration;

use App\Models\Migration;
use RuntimeException;
use Throwable;

class DownMigrationDispatcher extends AbstractMigrationDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$migrations = $this->getMigrationsToRollback($payload)) return $this->command->logger->info('Не найдено миграций для отката.');

        foreach($migrations as $row) if(!$this->rollbackMigration($row)) return false;

        $this->command->logger->info('Откат миграций успешно завершен!'); return true;
    }

    private function getMigrationsToRollback(string|int|null $target): array
    {
        $query = Migration::query()->orderByDesc('id');

        return match($target)
        {
            null => $query->where('batch', Migration::query()->max('batch'))->get()->all() ?: [], 'all' => $query->get()->all(),

            default => $query->where('id', (int) $target)->get()->all()
        };
    }

    private function rollbackMigration(object $row): bool
    {
        $this->command->logger->comment("Откат миграции {$row->migration} (ID: {$row->id})... ");

        if(!file_exists($row->migration))
        {
            $this->command->logger->error("Файл миграции {$row->migration} не найден. Отмена операции."); return false;
        }

        try
        {
            if(!is_object($instance = require $row->migration) || !method_exists($instance, 'down'))
            {
                throw new RuntimeException("Файл миграции должен возвращать объект с методом down()");
            }

            $instance->down(); Migration::query()->where('id', $row->id)->delete();

            $this->command->logger->info("Миграция {$row->migration} успешно откачена."); return true;
        }
        catch(Throwable $e)
        {
            $this->command->logger->error("Ошибка при откате миграции {$row->migration}: {$e->getMessage()}"); return false;
        }
    }
}
