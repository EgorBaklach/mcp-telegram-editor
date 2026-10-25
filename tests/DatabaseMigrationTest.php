<?php namespace Tests;

use App\Models\TestRecord;
use Cli\Commands\MigrateCommand;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\Migration\{CreateDispatcher, DownDispatcher, UpDispatcher};
use Magistrale\Logging\ConsoleLogger;
use Magistrale\Models\Migration;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\{BufferedOutput, NullOutput};
use Symfony\Component\Filesystem\Filesystem;

class DatabaseMigrationTest extends TestCase
{
    private Capsule $capsule;

    private MigrateCommand $command;

    private Filesystem $fs;

    /** @var array<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->capsule = $this->container->get(Capsule::class);
        $this->capsule->getConnection()->beginTransaction();

        $this->fs = $this->container->get(Filesystem::class);

        $this->command = $this->container->get(MigrateCommand::class);
        $this->command->setContainer($this->container);
        $this->command->construct();
        $this->command->logger->setOutput(new NullOutput());
    }

    protected function tearDown(): void
    {
        foreach($this->tempFiles as $file)
        {
            if($this->fs->exists($file)) $this->fs->remove($file);
        }

        if($this->capsule->getConnection()->transactionLevel() > 0)
        {
            $this->capsule->getConnection()->rollBack();
        }

        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('Подключение к PostgreSQL через Eloquent позволяет создавать и читать записи в транзакции')]
    public function testEloquentConnectionAndCrud(): void
    {
        $record = TestRecord::create(['message' => 'unit_test_entry']);

        $this->assertNotNull($record->id);
        $this->assertSame('unit_test_entry', TestRecord::find($record->id)?->message);
    }

    #[TestDox('CreateDispatcher: возвращает false (и код возврата Command::FAILURE) при пустом имени и создаёт файл при валидном')]
    public function testCreateDispatcherValidationAndFileCreation(): void
    {
        $dispatcher = $this->container->get(CreateDispatcher::class)->build($this->command);

        $this->assertFalse($dispatcher->dispatch(null));
        $this->assertFalse($dispatcher->dispatch(''));

        $before = glob('database/migrations/*_create_temp_audit_table.php') ?: [];
        $this->assertTrue($dispatcher->dispatch('CreateTempAuditTable'));
        $after = array_diff(glob('database/migrations/*_create_temp_audit_table.php') ?: [], $before);

        $this->assertCount(1, $after);
        $createdFile = reset($after);
        $this->tempFiles[] = $createdFile;
        $this->assertFileExists($createdFile);

        $output = new BufferedOutput();
        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--new' => null]), $output));
    }

    #[TestDox('UpDispatcher и DownDispatcher: накатывают миграцию, проверяют невалидный файл и безопасно откатывают по batch, ID и all')]
    public function testUpAndDownDispatchersLifecycleAndEdgeCases(): void
    {
        $up = $this->container->get(UpDispatcher::class)->build($this->command);
        $down = $this->container->get(DownDispatcher::class)->build($this->command);

        $validFile = 'database/migrations/9999_12_31_235958_test_valid_migration.php';
        $this->tempFiles[] = $validFile;
        $this->fs->dumpFile($validFile, "<?php\nreturn new class {\n    public function up(): void {}\n    public function down(): void {}\n};");

        $this->assertTrue($up->dispatch());
        $record = Migration::where('migration', $validFile)->first();
        $this->assertNotNull($record);

        // Повторный вызов up, когда нет новых миграций
        $this->assertTrue($up->dispatch());

        // Нечисловой таргет (--down=1abc или --down=invalid) не должен откатывать чужие миграции
        $this->assertTrue($down->dispatch('invalid_target'));
        $this->assertTrue($down->dispatch('1abc'));
        $this->assertNotNull(Migration::find($record->id), 'Миграция не должна откатываться при невалидном строковом ID');

        // Откат по конкретному ID
        $this->assertTrue($down->dispatch((string) $record->id));
        $this->assertNull(Migration::find($record->id));

        // Повторный накат и откат последнего батча (null)
        $this->assertTrue($up->dispatch());
        $this->assertTrue($down->dispatch(null));
        $this->assertNull(Migration::where('migration', $validFile)->first());

        // Если файл миграции удалён с диска, откат должен вернуть false
        $this->assertTrue($up->dispatch());
        $this->fs->remove($validFile);
        $this->assertFalse($down->dispatch(null));
    }

    #[TestDox('UpDispatcher: возвращает false, если файл миграции не возвращает объект с методом up()')]
    public function testUpDispatcherFailsOnBrokenMigrationFile(): void
    {
        $up = $this->container->get(UpDispatcher::class)->build($this->command);

        $brokenFile = 'database/migrations/9999_12_31_235959_test_broken_migration.php';
        $this->tempFiles[] = $brokenFile;
        $this->fs->dumpFile($brokenFile, "<?php\nreturn new class {};");

        $this->assertFalse($up->dispatch());
    }
}
