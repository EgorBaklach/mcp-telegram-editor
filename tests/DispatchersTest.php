<?php namespace Tests;

use Cli\Commands\MigrateCommand;
use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\Migration\UpDispatcher;
use Magistrale\Dispatchers\Migration\DownDispatcher;
use Magistrale\Dispatchers\Migration\CreateDispatcher;
use Magistrale\Dispatchers\Migration\AbstractDispatcher;
use App\Models\Migration;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;

class DispatchersTest extends TestCase
{
    private Capsule $capsule;
    private UpDispatcher $upDispatcher;
    private DownDispatcher $downDispatcher;
    private CreateDispatcher $createDispatcher;
    private MigrateCommand $command;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($app);

        $this->capsule = $container->get(Capsule::class);
        $this->upDispatcher = $container->get(UpDispatcher::class);
        $this->downDispatcher = $container->get(DownDispatcher::class);
        $this->createDispatcher = $container->get(CreateDispatcher::class);

        $this->command = new MigrateCommand();
        $this->command->setContainer($container);
        $this->command->construct();
        $this->command->logger->setOutput(new \Symfony\Component\Console\Output\NullOutput());

        $this->upDispatcher->build($this->command);
        $this->downDispatcher->build($this->command);
        $this->createDispatcher->build($this->command);
    }

    protected function tearDown(): void
    {
        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('Проверяет инициализацию таблицы и корректность выполнения run_up()')]
    public function testRunUpExecutesCorrectly(): void
    {
        // 1. Билдим движок (создаст таблицу migrations, если её нет)
        $this->assertTrue($this->capsule::schema()->hasTable('migrations'));

        // Очищаем БД от таблиц миграций из-за других тестов
        $this->capsule::schema()->dropIfExists('test_records');

        // 2. Очищаем таблицу миграций для чистоты теста
        Migration::query()->truncate();

        // 3. Выполняем миграции первый раз
        $result = $this->upDispatcher->dispatch();
        $this->assertTrue($result, 'Метод dispatch() должен вернуть true');

        // 4. Проверяем, что в таблице migrations появились записи
        $count = Migration::query()->count();
        $this->assertGreaterThan(0, $count, 'После dispatch() в таблице должны появиться записи о выполненных миграциях');

        // 5. Повторный запуск dispatch() не должен дублировать миграции
        $resultSecond = $this->upDispatcher->dispatch();
        $this->assertTrue($resultSecond, 'Повторный запуск dispatch() должен пройти успешно');

        $countSecond = Migration::query()->count();
        $this->assertEquals($count, $countSecond, 'Количество записей в БД не должно измениться при повторном запуске, так как новых миграций нет');
    }

    #[TestDox('Проверяет корректность выполнения отката миграций через DownDispatcher')]
    public function testRunDownExecutesCorrectly(): void
    {
        $this->capsule::schema()->dropIfExists('test_records');
        Migration::query()->truncate();

        // 1. Накатываем миграции
        $this->assertTrue($this->upDispatcher->dispatch(), 'Миграции должны успешно накатиться');
        $this->assertTrue($this->capsule::schema()->hasTable('test_records'), 'Таблица test_records должна создаться после накатывания');
        $this->assertGreaterThan(0, Migration::query()->count());

        // 2. Откатываем миграции (последний батч)
        $result = $this->downDispatcher->dispatch(null);
        $this->assertTrue($result, 'Метод dispatch() должен вернуть true');

        // 3. Проверяем удаление таблиц и записей
        $this->assertFalse($this->capsule::schema()->hasTable('test_records'), 'Таблица test_records должна быть удалена после down()');
        $this->assertEquals(0, Migration::query()->count(), 'Записи о миграциях должны быть удалены из БД');
    }

    #[TestDox('Проверяет расширенное поведение отката миграций: all, null и по ID')]
    public function testAdvancedRollbackBehaviors(): void
    {
        $this->capsule::schema()->dropIfExists('test_records');
        $this->capsule::schema()->dropIfExists('test_table_a');
        $this->capsule::schema()->dropIfExists('test_table_b');
        $this->capsule::schema()->dropIfExists('test_table_c');
        Migration::query()->truncate();

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
            $this->upDispatcher->dispatch();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'));

            // 2. Создаем еще два файла
            file_put_contents($fileB, sprintf($template, 'test_table_b', 'test_table_b'));
            file_put_contents($fileC, sprintf($template, 'test_table_c', 'test_table_c'));

            // Накатываем (test_table_b + test_table_c) -> батч 2
            $this->upDispatcher->dispatch();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_c'));

            // 3. Откат последнего батча (target = null)
            $this->downDispatcher->dispatch(null);

            $this->assertFalse($this->capsule::schema()->hasTable('test_table_b'), 'Таблица B должна быть удалена при откате батча');
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_c'), 'Таблица C должна быть удалена при откате батча');
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'), 'Таблица A должна остаться, так как она в 1 батче');

            // 4. Откат по ID (откатим test_table_a)
            $id = Migration::query()->where('migration', $fileA)->value('id');
            $this->downDispatcher->dispatch($id);

            $this->assertFalse($this->capsule::schema()->hasTable('test_table_a'), 'Таблица A должна быть удалена при откате по ID');

            // 5. Откат всех (target = 'all')
            // Сначала снова накатываем все недостающие миграции (a, b, c) -> батч 3
            $this->upDispatcher->dispatch();
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_a'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertTrue($this->capsule::schema()->hasTable('test_table_c'));

            // Откатываем абсолютно все миграции (test_records, a, b, c)
            $this->downDispatcher->dispatch('all');

            $this->assertFalse($this->capsule::schema()->hasTable('test_records'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_a'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_b'));
            $this->assertFalse($this->capsule::schema()->hasTable('test_table_c'));
            $this->assertEquals(0, Migration::query()->count(), 'Таблица миграций должна быть пустой');
        }
        finally
        {
            if(file_exists($fileA)) unlink($fileA);
            if(file_exists($fileB)) unlink($fileB);
            if(file_exists($fileC)) unlink($fileC);
        }
    }
    #[TestDox('Проверяет создание нового файла миграции через CreateDispatcher')]
    public function testCreateMigrationExecutesCorrectly(): void
    {
        $payload = 'TestCreateUserTable';
        $result = $this->createDispatcher->dispatch($payload);

        $this->assertTrue($result, 'Метод dispatch() должен вернуть true');

        $baseDir = getcwd() . '/database/migrations/';
        $files = glob($baseDir . '*_test_create_user_table.php');

        $this->assertNotEmpty($files, 'Файл миграции должен быть создан');

        $createdFile = $files[0];
        $this->assertFileExists($createdFile);

        $content = file_get_contents($createdFile);
        $this->assertStringContainsString('public function up(): void', $content, 'Файл должен содержать метод up()');
        $this->assertStringContainsString('public function down(): void', $content, 'Файл должен содержать метод down()');

        // Cleanup
        unlink($createdFile);
    }
}
