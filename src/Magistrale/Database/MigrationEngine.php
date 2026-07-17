<?php namespace Magistrale\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Laminas\Stdlib\Glob;
use Magistrale\Logging\MigrationLoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @method bool up()
 * @method bool down(mixed $option)
 */
class MigrationEngine
{
    private static bool $init = false;

    private const prefix = 'run_';

    public function __construct(private readonly Capsule $capsule, private readonly MigrationLoggerInterface $logger) {}

    public function build(OutputInterface $output): void
    {
        if(static::$init) return; static::$init = true;

        $this->logger->setOutput($output);

        if(!$this->capsule::schema()->hasTable('migrations'))
        {
            $this->logger->comment('Создается таблица migrations...');

            $this->capsule::schema()->create('migrations', function ($table)
            {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });

            $this->logger->info('Таблица migrations успешно создана.');
        }
    }

    public function __call(string $name, array $attributes): ?bool
    {
        return static::$init ? $this->{self::prefix.$name}(...$attributes) : throw new RuntimeException("Сначала нужно вызвать метод build!");
    }

    private function run_up(): bool
    {
        if(empty($files = $this->getPendingFiles()))
        {
            $this->logger->info('База данных в актуальном состоянии. Нет новых миграций.'); return true;
        }

        $batch = $this->capsule::table('migrations')->max('batch') ?? 0; foreach($files as $file) if(!$this->applyMigration($file, $batch + 1)) return false;

        $this->logger->info('Все новые миграции успешно применены!'); return true;
    }

    private function getPendingFiles(): array
    {
        $files = Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true);

        if($last = $this->capsule::table('migrations')->orderByDesc('batch')->orderByDesc('id')->value('migration'))
        {
            if(($index = array_search($last, $files)) !== false) return array_slice($files, $index + 1);
        }

        return $files;
    }

    private function applyMigration(string $file, int $batch): bool
    {
        $this->logger->comment("Применение миграции {$file}... ");

        try
        {
            if(!is_object($instance = require $file) || !method_exists($instance, 'up'))
            {
                throw new RuntimeException("Файл миграции {$file} должен возвращать анонимный класс с методом up()");
            }

            $instance->up(); $this->capsule::table('migrations')->insert(['migration' => $file, 'batch' => $batch]);

            $this->logger->info("Миграция {$file} успешно выполнена."); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error("Ошибка при выполнении миграции {$file}: {$e->getMessage()}"); return false;
        }
    }

    /**
     * Rolls back migrations.
     *
     * @param string|int|null $target 'all' for all, numeric ID for specific migration, null for last batch.
     */
    private function run_down(string|int|null $target): bool
    {
        if(($migrations = $this->getMigrationsToRollback($target))->isEmpty())
        {
            $this->logger->info('Не найдено миграций для отката.'); return true;
        }

        foreach($migrations as $row)
        {
            if(!$this->rollbackMigration($row)) return false;
        }

        $this->logger->info('Откат миграций успешно завершен!'); return true;
    }

    private function getMigrationsToRollback(string|int|null $target): \Illuminate\Support\Collection
    {
        $query = $this->capsule::table('migrations')->orderByDesc('id');

        if($target === null) return ($lastBatch = $this->capsule::table('migrations')->max('batch')) ? $query->where('batch', $lastBatch)->get() : new \Illuminate\Support\Collection();

        if($target === 'all') return $query->get();

        return $query->where('id', (int)$target)->get();
    }

    private function rollbackMigration(object $row): bool
    {
        $this->logger->comment("Откат миграции {$row->migration} (ID: {$row->id})... ");

        if(!file_exists($row->migration))
        {
            $this->logger->error("Файл миграции {$row->migration} не найден. Отмена операции."); return false;
        }

        try
        {
            if(!is_object($instance = require $row->migration) || !method_exists($instance, 'down')) throw new RuntimeException("Файл миграции должен возвращать объект с методом down()");

            $instance->down(); $this->capsule::table('migrations')->where('id', $row->id)->delete();

            $this->logger->info("Миграция {$row->migration} успешно откачена."); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error("Ошибка при откате миграции {$row->migration}: {$e->getMessage()}"); return false;
        }
    }
}
