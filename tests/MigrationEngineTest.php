<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Database\MigrationEngine;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;

class MigrationEngineTest extends TestCase
{
    private Capsule $capsule;
    private MigrationEngine $engine;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($app);

        $this->capsule = $container->get(Capsule::class);
        $this->engine = $container->get(MigrationEngine::class);

        // Сбрасываем статический флаг инициализации для корректных тестов
        $reflector = new ReflectionClass(MigrationEngine::class);
        $initProperty = $reflector->getProperty('init');
        $initProperty->setAccessible(true);
        $initProperty->setValue(null, false);
    }

    #[TestDox('Проверяет инициализацию таблицы и корректность выполнения run_up()')]
    public function testRunUpExecutesCorrectly(): void
    {
        // 1. Билдим движок (создаст таблицу migrations, если её нет)
        $this->engine->build(new \Symfony\Component\Console\Output\NullOutput());
        $this->assertTrue($this->capsule::schema()->hasTable('migrations'));

        // Очищаем БД от таблиц миграций из-за других тестов
        $this->capsule::schema()->dropIfExists('test_records');

        // 2. Очищаем таблицу миграций для чистоты теста
        $this->capsule::table('migrations')->truncate();

        // 3. Выполняем миграции первый раз (метод up вызывает приватный run_up)
        $result = $this->engine->up();
        $this->assertTrue($result, 'Метод run_up() должен вернуть true');

        // 4. Проверяем, что в таблице migrations появились записи
        $count = $this->capsule::table('migrations')->count();
        $this->assertGreaterThan(0, $count, 'После run_up() в таблице должны появиться записи о выполненных миграциях');

        // 5. Повторный запуск run_up() не должен дублировать миграции
        $resultSecond = $this->engine->up();
        $this->assertTrue($resultSecond, 'Повторный запуск run_up() должен пройти успешно');
        
        $countSecond = $this->capsule::table('migrations')->count();
        $this->assertEquals($count, $countSecond, 'Количество записей в БД не должно измениться при повторном запуске, так как новых миграций нет');
    }

    #[TestDox('Проверяет корректность выполнения отката миграций через run_down()')]
    public function testRunDownExecutesCorrectly(): void
    {
        $this->engine->build(new \Symfony\Component\Console\Output\NullOutput());
        $this->capsule::schema()->dropIfExists('test_records');
        $this->capsule::table('migrations')->truncate();

        // 1. Накатываем миграции
        $this->assertTrue($this->engine->up(), 'Миграции должны успешно накатиться');
        $this->assertTrue($this->capsule::schema()->hasTable('test_records'), 'Таблица test_records должна создаться после up()');
        $this->assertGreaterThan(0, $this->capsule::table('migrations')->count());

        // 2. Откатываем миграции (последний батч)
        $result = $this->engine->down(null);
        $this->assertTrue($result, 'Метод run_down() должен вернуть true');
        
        // 3. Проверяем удаление таблиц и записей
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'), 'Таблица test_records должна быть удалена после down()');
        $this->assertEquals(0, $this->capsule::table('migrations')->count(), 'Записи о миграциях должны быть удалены из БД');
    }
}
