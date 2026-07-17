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

    #[TestDox('Проверяет расширенное поведение отката миграций: all, null и по ID')]
    public function testAdvancedRollbackBehaviors(): void
    {
        $this->engine->build(new \Symfony\Component\Console\Output\NullOutput());
        $this->capsule::schema()->dropIfExists('test_records');
        $this->capsule::schema()->dropIfExists('test_table_a');
        $this->capsule::schema()->dropIfExists('test_table_b');
        $this->capsule::schema()->dropIfExists('test_table_c');
        $this->capsule::table('migrations')->truncate();

        $baseDir = __DIR__ . '/../database/migrations/';
        $fileA = $baseDir . '9999_01_01_000001_create_table_a.php';
        $fileB = $baseDir . '9999_01_01_000002_create_table_b.php';
        $fileC = $baseDir . '9999_01_01_000003_create_table_c.php';

        $template = "<?php return new class { public function up() { \Illuminate\Database\Capsule\Manager::schema()->create('%s', function (\$t) { \$t->increments('id'); }); } public function down() { \Illuminate\Database\Capsule\Manager::schema()->dropIfExists('%s'); } };";

        try
        {
            // 1. Создаем первый файл
            file_put_contents($fileA, sprintf($template, 'test_table_a', 'test_table_a'));
            
            // Накатываем (test_records + test_table_a) -> батч 1
            $this->engine->up();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'));

            // 2. Создаем еще два файла
            file_put_contents($fileB, sprintf($template, 'test_table_b', 'test_table_b'));
            file_put_contents($fileC, sprintf($template, 'test_table_c', 'test_table_c'));
            
            // Накатываем (test_table_b + test_table_c) -> батч 2
            $this->engine->up();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_c'));

            // 3. Откат последнего батча (target = null)
            $this->engine->down(null);
            
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_b'), 'Таблица B должна быть удалена при откате батча');
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_c'), 'Таблица C должна быть удалена при откате батча');
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'), 'Таблица A должна остаться, так как она в 1 батче');
            
            // 4. Откат по ID (откатим test_table_a)
            $id = $this->capsule::table('migrations')->where('migration', $fileA)->value('id');
            $this->engine->down($id);
            
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_a'), 'Таблица A должна быть удалена при откате по ID');

            // 5. Откат всех (target = 'all')
            // Сначала снова накатываем все недостающие миграции (a, b, c) -> батч 3
            $this->engine->up();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_c'));

            // Откатываем абсолютно все миграции (test_records, a, b, c)
            $this->engine->down('all');

            $this->assertFalse($this->capsule::schema()->hasTable('test_records'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_a'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_c'));
            $this->assertEquals(0, $this->capsule::table('migrations')->count(), 'Таблица миграций должна быть пустой');
        }
        finally
        {
            if(file_exists($fileA)) unlink($fileA);
            if(file_exists($fileB)) unlink($fileB);
            if(file_exists($fileC)) unlink($fileC);
        }
    }
}
