<?php namespace Magistrale\Dispatchers\Migration;

use Laminas\Stdlib\Glob;
use App\Models\Migration;
use Throwable;

class UpDispatcher extends AbstractDispatcher
{
    protected const method = 'up';

    public function dispatch(mixed $payload = null): bool
    {
        if(!$files = $this->getPendingFiles()) return $this->command->logger->info('База данных в актуальном состоянии. Нет новых миграций.');

        $batch = Migration::query()->max('batch') ?? 0; foreach($files as $file) if(!$this->applyMigration($file, $batch + 1)) return false;

        $this->command->logger->info('Все новые миграции успешно применены!'); return true;
    }

    private function getPendingFiles(): array
    {
        return array_values(array_diff(Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true), Migration::query()->select('migration')->pluck('migration')->all()));
    }

    private function applyMigration(string $file, int $batch): bool
    {
        $this->command->logger->comment("Применение миграции {$file}... ");

        try
        {
            $this->runMigration($file); Migration::query()->create(['migration' => $file, 'batch' => $batch]);

            $this->command->logger->info("Миграция {$file} успешно выполнена."); return true;
        }
        catch(Throwable $e)
        {
            $this->command->logger->error("Ошибка при выполнении миграции {$file}: {$e->getMessage()}"); return false;
        }
    }
}
