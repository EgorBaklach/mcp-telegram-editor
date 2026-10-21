<?php namespace Tests;

use Cli\Commands\SourceMonitorCommand;
use Framework\Application;
use League\Container\DefinitionContainerInterface;
use Magistrale\Agents\{AgyAgent, HermesAgent};
use Magistrale\Clients\Redis;
use Magistrale\Dispatchers\SourceMonitor\OpenRouterDispatcher;
use Magistrale\Logging\ConsoleLogger;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\{BufferedOutput, NullOutput};
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class SourceMonitorTest extends TestCase
{
    private $container;

    private Redis $redis;

    private Filesystem $fs;

    private SourceMonitorCommand $command;

    protected function setUp(): void
    {
        $config = require __DIR__.'/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $this->container = $containerProperty->getValue($app);

        $this->redis = $this->container->get(Redis::class);
        $this->fs = $this->container->get(Filesystem::class);
        $this->redis->releaseLock('lock:source:openrouter');
        $this->redis->releaseLock('lock:source:test');

        $this->command = new SourceMonitorCommand();
        $this->command->setContainer($this->container);
        $this->command->construct();
        $this->command->logger->setOutput(new NullOutput());
    }

    protected function tearDown(): void
    {
        $this->redis->releaseLock('lock:source:openrouter');
        $this->redis->releaseLock('lock:source:test');
        parent::tearDown();
    }

    #[TestDox('Redis: атомарно захватывает блокировку (SET NX EX), отклоняет дубли и освобождает (DEL)')]
    public function testRedisAtomicLockLifecycle(): void
    {
        $this->assertFalse($this->redis->isLocked('lock:source:test', 30));
        $this->assertTrue($this->redis->isLocked('lock:source:test', 30));
        $this->assertTrue($this->redis->releaseLock('lock:source:test'));
        $this->assertFalse($this->redis->releaseLock('lock:source:test'));
        $this->assertFalse($this->redis->isLocked('lock:source:test', 30));
    }

    #[TestDox('OpenRouterDispatcher: создаёт эфемерный HOME через AgyAgent, запускает процесс и удаляет HOME + Redis-лок в finally')]
    public function testDispatcherAcquiresLockRunsProcessAndReleasesLock(): void
    {
        $expectedHome = sys_get_temp_dir().'/agy-home-openrouter';
        $agent = new class($this->container, $this->fs, $expectedHome) extends AgyAgent
        {
            public function __construct(DefinitionContainerInterface $container, Filesystem $fs, private string $home)
            {
                parent::__construct($container, $fs);
            }

            protected function createProcess(string $prompt): Process
            {
                return new Process(['php', '-r', 'if(!is_dir(getenv("HOME")."/.gemini/antigravity-cli")) exit(1); echo "No new models";'], null, ['HOME' => $this->home]);
            }
        };

        $this->container->add(AgyAgent::class, $agent);
        $dispatcher = $this->container->get(OpenRouterDispatcher::class);

        $this->assertTrue($dispatcher->build($this->command)->dispatch());
        $this->assertDirectoryDoesNotExist($expectedHome, 'Эфемерный каталог HOME должен быть полностью удален в finally');
        $this->assertFalse($this->redis->isLocked('lock:source:openrouter', 10), 'Лок должен быть освобожден в finally после завершения');
    }

    #[TestDox('OpenRouterDispatcher: пропускает запуск при активном Redis-локе и не сбрасывает чужой лок')]
    public function testDispatcherSkipsWhenLockAlreadyHeld(): void
    {
        $this->assertFalse($this->redis->isLocked('lock:source:openrouter', 60));

        $processCalled = false;
        $agent = new class($this->container, $this->fs, $processCalled) extends AgyAgent
        {
            public function __construct(DefinitionContainerInterface $container, Filesystem $fs, private bool &$called)
            {
                parent::__construct($container, $fs);
            }

            protected function createProcess(string $prompt): Process
            {
                $this->called = true; return new Process(['php', '-v']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);
        $dispatcher = $this->container->get(OpenRouterDispatcher::class);

        $this->assertTrue($dispatcher->build($this->command)->dispatch());
        $this->assertFalse($processCalled, 'Процесс не должен запускаться, если лок уже удерживается');
        $this->assertTrue($this->redis->isLocked('lock:source:openrouter', 60), 'Чужой активный лок должен остаться нетронутым');
    }

    #[TestDox('OpenRouterDispatcher: возвращает false при ошибке процесса, но очищает HOME и Redis-лок в finally')]
    public function testDispatcherReleasesLockOnProcessFailure(): void
    {
        $expectedHome = sys_get_temp_dir().'/agy-home-openrouter';
        $agent = new class($this->container, $this->fs) extends AgyAgent
        {
            protected function createProcess(string $prompt): Process
            {
                return new Process(['php', '-r', 'fwrite(STDERR, "Agent error"); exit(1);']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);
        $dispatcher = $this->container->get(OpenRouterDispatcher::class);

        $this->assertFalse($dispatcher->build($this->command)->dispatch());
        $this->assertDirectoryDoesNotExist($expectedHome, 'Эфемерный HOME обязан удаляться даже при сбое процесса');
        $this->assertFalse($this->redis->isLocked('lock:source:openrouter', 10), 'Лок обязан освобождаться даже при сбое процесса');
    }

    #[TestDox('SourceMonitorCommand: поддерживает запуск дефолтного агента (agy) и выбор агента через --openrouter=hermes')]
    public function testSourceMonitorCommandExecution(): void
    {
        $agent = new class($this->container, $this->fs) extends AgyAgent
        {
            protected function createProcess(string $prompt): Process
            {
                return new Process(['php', '-r', 'echo "OK";']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);

        $output = new BufferedOutput();
        $this->command->logger = new ConsoleLogger();
        $this->assertEquals(Command::INVALID, $this->command->run(new ArrayInput([]), $output));
        $this->assertStringContainsString('Не указана опция!', $output->fetch());

        $this->command->logger = new ConsoleLogger();
        $this->assertEquals(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => null]), $output));

        $this->command->logger = new ConsoleLogger();
        $this->assertEquals(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'hermes']), $output));
        $this->assertStringContainsString('Запуск AI-агента (hermes)', $output->fetch());

        $dispatcher = $this->container->get(OpenRouterDispatcher::class)->build($this->command);
        $this->assertFalse($dispatcher->dispatch('unknown_agent'));
        $this->assertInstanceOf(HermesAgent::class, $this->container->get(HermesAgent::class));
    }
}
