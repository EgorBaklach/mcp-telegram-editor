<?php namespace Magistrale\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Laminas\Stdlib\Glob;
use Magistrale\Logging\MigrationLoggerInterface;

class MigrationEngine
{
    private bool $tableExists = false;

    public function __construct(private readonly Capsule $capsule) {}

    /**
     * Checks if migrations table exists (caching the result in memory).
     */
    private function checkTableExists(): bool
    {
        if ($this->tableExists) {
            return true;
        }
        $this->tableExists = $this->capsule::schema()->hasTable('migrations');
        return $this->tableExists;
    }

    /**
     * Creates migrations table if not exists.
     */
    private function ensureMigrationsTable(MigrationLoggerInterface $logger): void
    {
        if (!$this->checkTableExists()) {
            $logger->comment('Создается таблица migrations...');
            $this->capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
            $this->tableExists = true;
            $logger->info('Таблица migrations успешно создана.');
        }
    }

    /**
     * Runs all pending migrations (up).
     */
    public function up(MigrationLoggerInterface $logger): bool
    {
        $this->ensureMigrationsTable($logger);

        // 1. Get executed migrations (tracked by full paths)
        $executed = $this->capsule::table('migrations')->pluck('migration')->toArray();

        // 2. Scan for migration files
        $files = Glob::glob('database/migrations/*.php', Glob::GLOB_BRACE, true);
        sort($files);

        $pendingFiles = [];
        foreach ($files as $file) {
            $absolutePath = realpath($file);
            if ($absolutePath && !in_array($absolutePath, $executed)) {
                $pendingFiles[] = $absolutePath;
            }
        }

        if (empty($pendingFiles)) {
            $logger->info('База данных в актуальном состоянии. Нет новых миграций.');
            return true;
        }

        // Calculate next batch
        $batch = ($this->capsule::table('migrations')->max('batch') ?? 0) + 1;

        // Run migrations
        foreach ($pendingFiles as $file) {
            $name = basename($file);
            $logger->comment("Применение миграции {$name}... ");
            
            try {
                $migrationInstance = require $file;
                
                if (is_object($migrationInstance) && method_exists($migrationInstance, 'up')) {
                    $migrationInstance->up();
                } else {
                    throw new \RuntimeException("Файл миграции должен возвращать объект с методом up().");
                }

                $this->capsule::table('migrations')->insert([
                    'migration' => $file,
                    'batch'     => $batch,
                ]);

                $logger->info("Миграция {$name} успешно выполнена.");
            } catch (\Throwable $e) {
                $logger->error("Ошибка при выполнении миграции {$name}: {$e->getMessage()}");
                return false;
            }
        }

        $logger->info('Все новые миграции успешно применены!');
        return true;
    }

    /**
     * Rolls back migrations.
     * 
     * @param string|int|null $target 'all' for all, numeric ID for specific migration, null for last batch.
     */
    public function down(string|int|null $target, MigrationLoggerInterface $logger): bool
    {
        if (!$this->checkTableExists()) {
            $logger->info('Таблица migrations не найдена. Нечего откатывать.');
            return true;
        }

        $query = $this->capsule::table('migrations');

        if ($target === null) {
            // Rollback last batch
            $lastBatch = $this->capsule::table('migrations')->max('batch');
            if ($lastBatch === null) {
                $logger->info('Нет записей о выполненных миграциях.');
                return true;
            }
            $migrationsToRollback = $query->where('batch', $lastBatch)->orderBy('id', 'desc')->get();
        } elseif ($target === 'all') {
            // Rollback all
            $migrationsToRollback = $query->orderBy('id', 'desc')->get();
        } else {
            // Rollback specific ID
            $migrationsToRollback = $query->where('id', (int)$target)->get();
        }

        if ($migrationsToRollback->isEmpty()) {
            $logger->info('Не найдено миграций для отката.');
            return true;
        }

        foreach ($migrationsToRollback as $row) {
            $file = $row->migration;
            $name = basename($file);
            $logger->comment("Откат миграции {$name} (ID: {$row->id})... ");

            if (!file_exists($file)) {
                $logger->error("Файл миграции {$name} не найден по пути {$file}. Отмена операции.");
                return false;
            }

            try {
                $migrationInstance = require $file;
                if (is_object($migrationInstance) && method_exists($migrationInstance, 'down')) {
                    $migrationInstance->down();
                } else {
                    throw new \RuntimeException("Файл миграции должен возвращать объект с методом down().");
                }

                $this->capsule::table('migrations')->where('id', $row->id)->delete();
                $logger->info("Миграция {$name} успешно откачена.");
            } catch (\Throwable $e) {
                $logger->error("Ошибка при откате миграции {$name}: {$e->getMessage()}");
                return false;
            }
        }

        $logger->info('Откат миграций успешно завершен!');
        return true;
    }
}
