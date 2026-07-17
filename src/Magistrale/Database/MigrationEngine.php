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

        if (!$this->capsule::schema()->hasTable('migrations'))
        {
            $this->logger->comment('Создается таблица migrations...');

            $this->capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });

            $this->logger->info('Таблица migrations успешно создана.');
        }
    }

    public function __call(string $name, array $attributes): ?bool
    {
        return static::$init ? $this->{self::prefix.$name}(...$attributes) : throw new RuntimeException("Need to run method build first!");
    }

    private function query()
    {
        return $this->capsule::table('migrations');
    }

    private function run_up(): bool
    {


        $this->logger->info(json_encode(Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true)));



        return true;

        /*// 1. Сканируем директорию миграций через Glob
        $files = Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true);
        sort($files);
        $files = array_map('realpath', $files);
        $files = array_filter($files);

        // 2. Получаем последнюю выполненную миграцию по батчу
        $lastMigrationPath = (clone $this->queryBuilder)->orderBy('batch', 'desc')->orderBy('id', 'desc')->value('migration');

        $pendingFiles = [];
        if ($lastMigrationPath === null) {
            // Если в БД нет миграций, то все файлы считаются новыми
            $pendingFiles = $files;
        } else {
            $lastIndex = array_search(realpath($lastMigrationPath), $files);
            if ($lastIndex === false) {
                // Резервный вариант: если файл последней миграции переименован/удален на диске,
                // фильтруем по полному списку выполненных
                $executed = (clone $this->queryBuilder)->pluck('migration')->toArray();
                foreach ($files as $file) {
                    if (!in_array($file, $executed)) {
                        $pendingFiles[] = $file;
                    }
                }
            } else {
                // Собираем только файлы, идущие по списку строго после последней выполненной
                $pendingFiles = array_slice($files, $lastIndex + 1);
            }
        }

        if (empty($pendingFiles)) {
            $this->logger->info('База данных в актуальном состоянии. Нет новых миграций.');
            return true;
        }

        $batch = (clone $this->queryBuilder)->max('batch') ?? 0;
        $batch++;

        // Запуск миграций
        foreach ($pendingFiles as $file) {
            $name = basename($file);
            $this->logger->comment("Применение миграции {$name}... ");

            try {
                $migrationInstance = require $file;

                if (is_object($migrationInstance) && method_exists($migrationInstance, 'up')) {
                    $migrationInstance->up();
                } else {
                    throw new RuntimeException("Файл миграции должен возвращать объект с методом up().");
                }

                (clone $this->queryBuilder)->insert([
                    'migration' => $file,
                    'batch'     => $batch,
                ]);

                $this->logger->info("Миграция {$name} успешно выполнена.");
            } catch (Throwable $e) {
                $this->logger->error("Ошибка при выполнении миграции {$name}: {$e->getMessage()}");
                return false;
            }
        }

        $this->logger->info('Все новые миграции успешно применены!');
        return true;*/
    }

    /**
     * Rolls back migrations.
     *
     * @param string|int|null $target 'all' for all, numeric ID for specific migration, null for last batch.
     */
    private function run_down(string|int|null $target): bool
    {
        $this->logger->info($target);

        return true;

        /*$query = clone $this->queryBuilder;

        if ($target === null) {
            $lastBatch = (clone $this->queryBuilder)->max('batch');
            if ($lastBatch === null) {
                $this->logger->info('Нет записей о выполненных миграциях.');
                return true;
            }
            $migrationsToRollback = $query->where('batch', $lastBatch)->orderBy('id', 'desc')->get();
        } elseif ($target === 'all') {
            $migrationsToRollback = $query->orderBy('id', 'desc')->get();
        } else {
            $migrationsToRollback = $query->where('id', (int)$target)->get();
        }

        if ($migrationsToRollback->isEmpty()) {
            $this->logger->info('Не найдено миграций для отката.');
            return true;
        }

        foreach ($migrationsToRollback as $row) {
            $file = $row->migration;
            $name = basename($file);
            $this->logger->comment("Откат миграции {$name} (ID: {$row->id})... ");

            if (!file_exists($file)) {
                $this->logger->error("Файл миграции {$name} не найден по пути {$file}. Отмена операции.");
                return false;
            }

            try {
                $migrationInstance = require $file;
                if (is_object($migrationInstance) && method_exists($migrationInstance, 'down')) {
                    $migrationInstance->down();
                } else {
                    throw new RuntimeException("Файл миграции должен возвращать объект с методом down().");
                }

                (clone $this->queryBuilder)->where('id', $row->id)->delete();
                $this->logger->info("Миграция {$name} успешно откачена.");
            } catch (Throwable $e) {
                $this->logger->error("Ошибка при откате миграции {$name}: {$e->getMessage()}");
                return false;
            }
        }

        $this->logger->info('Откат миграций успешно завершен!');
        return true;*/
    }
}
