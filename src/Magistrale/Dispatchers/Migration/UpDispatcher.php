<?php namespace Magistrale\Dispatchers\Migration;

use Laminas\Stdlib\Glob;
use App\Models\Migration;
use RuntimeException;
use Throwable;

class UpDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(empty($files = $this->getPendingFiles())) return $this->command->logger->info('База данных в актуальном состоянии. Нет новых миграций.');

        $batch = Migration::query()->max('batch') ?? 0; foreach($files as $file) if(!$this->applyMigration($file, $batch + 1)) return false;

        $this->command->logger->info('Все новые миграции успешно применены!'); return true;
    }

    private function getPendingFiles(): array
    {
        $files = Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true);

        if($last = Migration::query()->orderByDesc('batch')->orderByDesc('id')->value('migration'))
        {
            if(($index = array_search($last, $files)) !== false) return array_slice($files, $index + 1);
        }

        return $files;
    }

    private function applyMigration(string $file, int $batch): bool
    {
        $this->command->logger->comment("Применение миграции {$file}... ");

        try
        {
            if(!is_object($instance = require $file) || !method_exists($instance, 'up'))
            {
                throw new RuntimeException("Файл миграции {$file} должен возвращать анонимный класс с методом up()");
            }

            $instance->up(); Migration::query()->create(['migration' => $file, 'batch' => $batch]);

            $this->command->logger->info("Миграция {$file} успешно выполнена."); return true;
        }
        catch(Throwable $e)
        {
            $this->command->logger->error("Ошибка при выполнении миграции {$file}: {$e->getMessage()}"); return false;
        }
    }
}
