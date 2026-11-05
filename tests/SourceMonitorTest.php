<?php namespace Tests;

use App\Dispatchers\Source\ReportDispatcher;
use App\Models\{FLOrder, HHVacancy, OpenRouterModel};
use Cli\Commands\{SourceMonitorCommand, SourceReportCommand};
use Illuminate\Database\Capsule\Manager as Capsule;
use League\Container\DefinitionContainerInterface;
use League\Plates\Engine as Template;
use Magistrale\Agents\{AgyAgent, HermesAgent};
use Magistrale\Contracts\RedisInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\{BufferedOutput, NullOutput};
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class SourceMonitorTest extends TestCase
{
    private RedisInterface $redis;

    private Filesystem $fs;

    private SourceMonitorCommand $command;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->redis = $this->container->get(RedisInterface::class);
        $this->fs = $this->container->get(Filesystem::class);
        $this->redis->unlock('lock:source:openrouter');
        $this->redis->unlock('lock:source:fl');
        $this->redis->unlock('lock:source:hh');
        $this->redis->unlock('lock:source:report');
        $this->redis->unlock('lock:source:test');

        $this->command = $this->container->get(SourceMonitorCommand::class);
        $this->command->setContainer($this->container);
        $this->command->construct();
        $this->command->logger->setOutput(new NullOutput());
    }

    protected function tearDown(): void
    {
        $this->redis->unlock('lock:source:openrouter');
        $this->redis->unlock('lock:source:fl');
        $this->redis->unlock('lock:source:hh');
        $this->redis->unlock('lock:source:report');
        $this->redis->unlock('lock:source:test');
        parent::tearDown();
    }

    #[TestDox('Redis и RedisMixin: кодируют RESP-фреймы, атомарно захватывают лок (SET NX EX), отклоняют дубли и освобождают лок (DEL)')]
    public function testRedisRespEncodingAndAtomicLockLifecycle(): void
    {
        $this->assertSame("*2\r\n$3\r\nDEL\r\n$4\r\ntest\r\n", $this->redis->encode(['DEL', 'test']));

        $this->assertFalse($this->redis->locked('lock:source:test', 30));
        $this->assertTrue($this->redis->locked('lock:source:test', 30));
        $this->assertTrue($this->redis->unlock('lock:source:test'));
        $this->assertFalse($this->redis->unlock('lock:source:test'));
    }

    #[TestDox('SourceMonitorCommand и AgyAgent: создают изолированный HOME, выполняют процесс и удаляют HOME + лок в finally')]
    public function testSourceMonitorCommandSuccessLifecycle(): void
    {
        $expectedHome = sys_get_temp_dir().'/agy-home-openrouter';

        $okAgent = new class($this->container, $this->fs, $expectedHome) extends AgyAgent
        {
            public function __construct(DefinitionContainerInterface $container, Filesystem $fs, private string $home)
            {
                parent::__construct($container, $fs);
            }

            protected function process(string $prompt): Process
            {
                return new Process(['php', '-r', 'if(!is_dir(getenv("HOME")."/.gemini/antigravity-cli")) exit(1); echo "No new models";'], null, ['HOME' => $this->home]);
            }
        };

        $this->container->add(AgyAgent::class, $okAgent);

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'agy']), new NullOutput()));
        $this->assertDirectoryDoesNotExist($expectedHome);
        $this->assertFalse($this->redis->locked('lock:source:openrouter', 10));
    }

    #[TestDox('SourceMonitorCommand: возвращает false при сбое процесса агента, но гарантированно удаляет HOME и снимает лок в finally')]
    public function testSourceMonitorCommandFailureCleanup(): void
    {
        $expectedHome = sys_get_temp_dir().'/agy-home-openrouter';

        $failAgent = new class($this->container, $this->fs) extends AgyAgent
        {
            protected function process(string $prompt): Process
            {
                return new Process(['php', '-r', 'fwrite(STDERR, "Fatal agent error"); exit(1);']);
            }
        };

        $this->container->add(AgyAgent::class, $failAgent);

        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--openrouter' => 'agy']), new NullOutput()));
        $this->assertDirectoryDoesNotExist($expectedHome);
        $this->assertFalse($this->redis->locked('lock:source:openrouter', 10));
    }

    #[TestDox('SourceMonitorCommand: пропускает выполнение при удерживаемом Redis-локе и не снимает чужую блокировку')]
    public function testCommandSkipsWhenAlreadyLocked(): void
    {
        $this->assertFalse($this->redis->locked('lock:source:openrouter', 60));

        $called = false;
        $agent = new class($this->container, $this->fs, $called) extends AgyAgent
        {
            public function __construct(DefinitionContainerInterface $container, Filesystem $fs, private bool &$called)
            {
                parent::__construct($container, $fs);
            }

            protected function process(string $prompt): Process
            {
                $this->called = true; return new Process(['php', '-v']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'agy']), new NullOutput()));
        $this->assertFalse($called);
        $this->assertTrue($this->redis->locked('lock:source:openrouter', 60));
    }

    #[TestDox('SourceMonitorCommand и CommandMixin: возвращают INVALID без флага, FAILURE без имени агента и SUCCESS при валидном агенте')]
    public function testSourceMonitorCommandExitCodesAndAgentSelection(): void
    {
        $agent = new class($this->container, $this->fs) extends AgyAgent
        {
            protected function process(string $prompt): Process
            {
                return new Process(['php', '-r', 'echo "OK";']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);
        $output = new BufferedOutput();

        $this->assertSame(Command::INVALID, $this->command->run(new ArrayInput([]), $output));

        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--openrouter' => null]), $output));
        $this->assertStringContainsString('Укажите доступного AI-агента', $output->fetch());

        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--openrouter' => 'unknown']), $output));

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'agy']), $output));

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--fl' => 'agy']), $output));

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--hh' => 'agy']), $output));

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'hermes']), $output));
        $this->assertInstanceOf(HermesAgent::class, $this->container->get(HermesAgent::class));
    }

    #[TestDox('SourceReportCommand и ReportDispatcher: агрегируют 24-часовую воронку из БД и отправляют сводный отчёт по шаблону report.plate.php')]
    public function testSourceReportCommandAndReportDispatcherFunnel(): void
    {
        $capsule = $this->container->get(Capsule::class);
        $capsule->getConnection()->beginTransaction();

        try
        {
            OpenRouterModel::query()->delete();
            FLOrder::query()->delete();
            HHVacancy::query()->delete();

            OpenRouterModel::insert([
                ['model_id' => 'v/m1:free', 'name' => 'M1', 'accessible' => 'free', 'published' => true, 'created_at' => date('Y-m-d H:i:s')],
                ['model_id' => 'v/m2:free', 'name' => 'M2', 'accessible' => 'free', 'published' => true, 'created_at' => date('Y-m-d H:i:s')],
                ['model_id' => 'v/m3:free', 'name' => 'M3', 'accessible' => 'free', 'published' => false, 'created_at' => date('Y-m-d H:i:s')],
                ['model_id' => 'v/old:free', 'name' => 'Old', 'accessible' => 'free', 'published' => true, 'created_at' => date('Y-m-d H:i:s', strtotime('-48 hours'))],
            ]);

            FLOrder::insert([
                ['project_id' => 101, 'hash' => md5('fl1'), 'title' => 'FL 1', 'link' => 'https://fl.ru/1', 'published' => true, 'created_at' => date('Y-m-d H:i:s')],
                ['project_id' => 102, 'hash' => md5('fl2'), 'title' => 'FL 2', 'link' => 'https://fl.ru/2', 'published' => false, 'created_at' => date('Y-m-d H:i:s')],
                ['project_id' => 103, 'hash' => md5('fl3'), 'title' => 'FL 3', 'link' => 'https://fl.ru/3', 'published' => null, 'created_at' => date('Y-m-d H:i:s')],
            ]);

            HHVacancy::insert([
                ['vacancy_id' => 201, 'hash' => md5('hh1'), 'title' => 'HH 1', 'link' => 'https://hh.ru/1', 'published' => true, 'created_at' => date('Y-m-d H:i:s')],
                ['vacancy_id' => 202, 'hash' => md5('hh2'), 'title' => 'HH 2', 'link' => 'https://hh.ru/2', 'published' => null, 'created_at' => date('Y-m-d H:i:s')],
            ]);

            $reportCommand = $this->container->get(SourceReportCommand::class);
            $reportCommand->setContainer($this->container)->construct();

            $this->assertSame(Command::SUCCESS, $reportCommand->run(new ArrayInput(['--daily' => true]), new NullOutput()));

            $dispatcher = $this->container->get(ReportDispatcher::class);
            $this->assertSame(['total' => 3, 'published' => 2], $openrouter = $dispatcher->callback->report('funnel', OpenRouterModel::class));
            $this->assertSame(['total' => 3, 'published' => 1], $fl = $dispatcher->callback->report('funnel', FLOrder::class));
            $this->assertSame(['total' => 2, 'published' => 1], $hh = $dispatcher->callback->report('funnel', HHVacancy::class));

            $rendered = $this->container->get(Template::class)->render('report', ['sources' => ['openrouter' => $openrouter, 'fl' => $fl, 'hh' => $hh]]);
            $this->assertStringContainsString('<b>🔹 OPENROUTER:</b><br>', $rendered);
            $this->assertStringContainsString('• Найдено за сутки: <b>3</b><br>', $rendered);
            $this->assertStringContainsString('• Опубликовано: <b>2</b><br>', $rendered);
        }
        finally
        {
            if($capsule->getConnection()->transactionLevel() > 0) $capsule->getConnection()->rollBack();

            $capsule->getConnection()->disconnect();
        }
    }
}
