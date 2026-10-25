<?php namespace Tests;

use App\Dispatchers\SourceMonitor\{FlDispatcher, OpenRouterDispatcher};
use Cli\Commands\SourceMonitorCommand;
use League\Container\DefinitionContainerInterface;
use Magistrale\Agents\{AgyAgent, HermesAgent};
use Magistrale\Contracts\RedisInterface;
use Magistrale\Logging\ConsoleLogger;
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

    #[TestDox('OpenRouterDispatcher и AgyAgent: создают изолированный HOME, выполняют процесс и удаляют HOME + лок в finally')]
    public function testOpenRouterDispatcherSuccessLifecycle(): void
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
        $dispatcher = $this->container->get(OpenRouterDispatcher::class)->build($this->command);

        $this->assertTrue($dispatcher->dispatch('agy'));
        $this->assertDirectoryDoesNotExist($expectedHome);
        $this->assertFalse($this->redis->locked('lock:source:openrouter', 10));
    }

    #[TestDox('OpenRouterDispatcher: возвращает false при сбое процесса агента, но гарантированно удаляет HOME и снимает лок в finally')]
    public function testOpenRouterDispatcherFailureCleanup(): void
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
        $dispatcher = $this->container->get(OpenRouterDispatcher::class)->build($this->command);

        $this->assertFalse($dispatcher->dispatch('agy'));
        $this->assertDirectoryDoesNotExist($expectedHome);
        $this->assertFalse($this->redis->locked('lock:source:openrouter', 10));
    }

    #[TestDox('OpenRouterDispatcher: пропускает выполнение при удерживаемом Redis-локе и не снимает чужую блокировку')]
    public function testDispatcherSkipsWhenAlreadyLocked(): void
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
        $dispatcher = $this->container->get(OpenRouterDispatcher::class)->build($this->command);

        $this->assertTrue($dispatcher->dispatch('agy'));
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

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::INVALID, $this->command->run(new ArrayInput([]), $output));

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--openrouter' => null]), $output));
        $this->assertStringContainsString('Укажите доступного AI-агента', $output->fetch());

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--openrouter' => 'unknown']), $output));

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'agy']), $output));

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--fl' => 'agy']), $output));
        $this->assertInstanceOf(FlDispatcher::class, $this->container->get(FlDispatcher::class));

        $this->command->logger = new ConsoleLogger();
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--openrouter' => 'hermes']), $output));
        $this->assertInstanceOf(HermesAgent::class, $this->container->get(HermesAgent::class));
    }
}
