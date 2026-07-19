<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use App\Models\TestRecord;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\Migration\UpDispatcher;
use Magistrale\Dispatchers\Migration\AbstractDispatcher;
use Magistrale\Logging\MigrationLoggerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;

class DatabaseTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        // Инициализируем контейнер приложения, чтобы сработал DatabaseServiceProvider
        $config = require __DIR__ . '/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($app);

        // Получаем Capsule для прямой работы
        $this->capsule = $container->get(Capsule::class);

        // Гарантируем, что миграции применены перед тестами БД
        $engine = $container->get(UpDispatcher::class);



        $this->capsule::schema()->dropIfExists('migrations');
        $this->capsule::schema()->dropIfExists('test_records');

        $command = new \Cli\Commands\MigrateCommand();
        $command->setContainer($container);
        $command->construct();
        $command->logger->setOutput(new \Symfony\Component\Console\Output\NullOutput());

        $engine->build($command);
        $engine->dispatch();
    }

    protected function tearDown(): void
    {
        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('Проверяет подключение к базе данных, запись тестовой строки через Eloquent и ее чтение')]
    public function testDatabaseConnectionAndEloquentOperations(): void
    {
        // 1. Проверяем, что соединение установлено
        $this->assertNotNull($this->capsule->getConnection()->getPdo());

        // 2. Очищаем таблицу перед тестом
        TestRecord::truncate();

        // 3. Создаем тестовую запись через Eloquent
        $messageText = 'Тестовое сообщение для проверки БД';
        $record = TestRecord::create([
            'message' => $messageText,
        ]);

        $this->assertNotNull($record->id);
        $this->assertEquals($messageText, $record->message);

        // 4. Считываем запись из базы данных
        $retrieved = TestRecord::find($record->id);
        $this->assertInstanceOf(TestRecord::class, $retrieved);
        $this->assertEquals($messageText, $retrieved->message);

        // 5. Очищаем таблицу после успешного теста
        TestRecord::truncate();
    }
}
