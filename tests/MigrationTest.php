<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Magistrale\Database\MigrationEngine;
use Magistrale\Logging\MigrationLoggerInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\Attributes\TestDox;

class MigrationTest extends TestCase
{
    private Capsule $capsule;
    private array $logs = [];
    private MigrationLoggerInterface $logger;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new \ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($app);

        $this->capsule = $container->get(Capsule::class);
        $this->logs = [];

        // Создаем логгер в памяти для проверки логов во время теста
        $this->logger = new class($this) implements MigrationLoggerInterface {
            public function __construct(private readonly MigrationTest $test) {}

            public function info(string $message): void
            {
                $this->test->addLog('info', $message);
            }

            public function comment(string $message): void
            {
                $this->test->addLog('comment', $message);
            }

            public function error(string $message): void
            {
                $this->test->addLog('error', $message);
            }
        };

        // Дропаем таблицы перед тестом
        $this->capsule::schema()->dropIfExists('test_records');
        $this->capsule::schema()->dropIfExists('migrations');
    }

    public function addLog(string $type, string $message): void
    {
        $this->logs[] = ['type' => $type, 'message' => $message];
    }

    protected function tearDown(): void
    {
        $this->capsule::schema()->dropIfExists('test_records');
        $this->capsule::schema()->dropIfExists('migrations');
    }

    #[TestDox('Проверяет полный цикл миграций: создание таблицы migrations, применение up, повторный up и откат down')]
    public function testMigrationLifecycle(): void
    {
        $engine = new MigrationEngine($this->capsule);

        // 1. Проверяем, что изначально таблиц нет
        $this->assertFalse($this->capsule::schema()->hasTable('migrations'));
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'));

        // 2. Запускаем миграцию вперед (up)
        $result = $engine->up($this->logger);
        $this->assertTrue($result);

        // Проверяем, что таблицы создались
        $this->assertTrue($this->capsule::schema()->hasTable('migrations'));
        $this->assertTrue($this->capsule::schema()->hasTable('test_records'));

        // Проверяем, что в базу записался полный абсолютный путь к файлу
        $executed = $this->capsule::table('migrations')->first();
        $this->assertNotNull($executed);
        $this->assertStringContainsString('database/migrations/', $executed->migration);
        $this->assertStringEndsWith('.php', $executed->migration);
        $this->assertEquals(1, $executed->batch);

        // 3. Запускаем up еще раз. Новых миграций быть не должно.
        $this->logs = []; // очистим логи перед повторным запуском
        $resultRepeat = $engine->up($this->logger);
        $this->assertTrue($resultRepeat);
        
        $hasNoNewMigrationsMsg = false;
        foreach ($this->logs as $log) {
            if (str_contains($log['message'], 'Нет новых миграций')) {
                $hasNoNewMigrationsMsg = true;
            }
        }
        $this->assertTrue($hasNoNewMigrationsMsg, 'Должно выводиться сообщение об актуальности БД.');

        // 4. Откатываем миграцию назад (down)
        $rollbackResult = $engine->down(null, $this->logger);
        $this->assertTrue($rollbackResult);

        // Проверяем, что таблица test_records была дропнута
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'));

        // Проверяем, что запись о миграции удалена из таблицы migrations
        $this->assertEquals(0, $this->capsule::table('migrations')->count());
    }

    #[TestDox('Проверяет откат миграции по ID и откат всех миграций')]
    public function testMigrationRollbackTargets(): void
    {
        $engine = new MigrationEngine($this->capsule);

        // Применяем миграции
        $engine->up($this->logger);
        $this->assertTrue($this->capsule::schema()->hasTable('test_records'));

        // Получаем ID записи миграции
        $executed = $this->capsule::table('migrations')->first();
        $migrationId = $executed->id;

        // Откатываем по конкретному ID
        $rollbackResult = $engine->down($migrationId, $this->logger);
        $this->assertTrue($rollbackResult);
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'));
        $this->assertEquals(0, $this->capsule::table('migrations')->count());

        // Заново применяем
        $engine->up($this->logger);
        $this->assertTrue($this->capsule::schema()->hasTable('test_records'));

        // Откатываем все ('all')
        $rollbackAll = $engine->down('all', $this->logger);
        $this->assertTrue($rollbackAll);
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'));
        $this->assertEquals(0, $this->capsule::table('migrations')->count());
    }
}
