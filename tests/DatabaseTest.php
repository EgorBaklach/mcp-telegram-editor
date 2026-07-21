<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use App\Models\TestRecord;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\Migration\UpDispatcher;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use Cli\Commands\MigrateCommand;
use Symfony\Component\Console\Output\NullOutput;

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
        $this->capsule->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->capsule->getConnection()->transactionLevel() > 0) {
            $this->capsule->getConnection()->rollBack();
        }
        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('Проверяет подключение к базе данных, запись тестовой строки через Eloquent и ее чтение')]
    public function testDatabaseConnectionAndEloquentOperations(): void
    {
        // 1. Проверяем, что соединение установлено
        $this->assertNotNull($this->capsule->getConnection()->getPdo());

        // 2. Создаем тестовую запись через Eloquent
        $messageText = 'Тестовое сообщение для проверки БД';
        $record = TestRecord::create([
            'message' => $messageText,
        ]);

        $this->assertNotNull($record->id);
        $this->assertEquals($messageText, $record->message);

        // 3. Считываем запись из базы данных
        $retrieved = TestRecord::find($record->id);
        $this->assertInstanceOf(TestRecord::class, $retrieved);
        $this->assertEquals($messageText, $retrieved->message);
    }
}
