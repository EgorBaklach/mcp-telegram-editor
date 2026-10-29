<?php namespace Tests;

use Cli\Commands\QueueManagerCommand;
use League\Container\DefinitionContainerInterface;
use Magistrale\Agents\AgyAgent;
use Magistrale\Contracts\RedisInterface;
use Magistrale\Dispatchers\Queue\WorkDispatcher;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\{BufferedOutput, NullOutput};
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class QueueTest extends TestCase
{
    private RedisInterface $redis;

    private Filesystem $fs;

    private QueueManagerCommand $command;

    private WorkDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->redis = $this->container->get(RedisInterface::class);
        $this->fs = $this->container->get(Filesystem::class);
        $this->cleanupRedis();

        $this->command = $this->container->get(QueueManagerCommand::class);
        $this->command->setContainer($this->container)->construct();
        $this->command->logger->setOutput(new NullOutput());

        $this->dispatcher = $this->container->get(WorkDispatcher::class);
    }

    protected function tearDown(): void
    {
        $this->cleanupRedis();
        parent::tearDown();
    }

    private function cleanupRedis(): void
    {
        foreach(['queue:pending', 'queue:active', 'queue:paused', 'lock:queue:work', 'lock:queue:status', 'lock:queue:switch', 'lock:queue:remove', 'lock:queue:clear', 'lock:source:openrouter', 'lock:source:fl', 'lock:source:hh'] as $key)
        {
            $this->redis->unlock($key);
        }
    }

    #[TestDox('QueueMixin::cron: корректно вычисляет совпадения 5-польных масок (*, */N, списки A,B,C и диапазоны A-B)')]
    public function testCronExpressionParser(): void
    {
        $ts0600 = mktime(6, 0, 0, 10, 29, 2026);
        $ts0610 = mktime(6, 10, 0, 10, 29, 2026);
        $ts0620 = mktime(6, 20, 0, 10, 29, 2026);
        $ts0700 = mktime(7, 0, 0, 10, 29, 2026);

        $this->assertSame($this->dispatcher, $this->dispatcher->cron('0 */6 * * *', $ts0600));
        $this->assertNull($this->dispatcher->cron('0 */6 * * *', $ts0700));
        $this->assertNull($this->dispatcher->cron('0 */6 * * *', $ts0610));

        $this->assertSame($this->dispatcher, $this->dispatcher->cron('10,30,50 * * * *', $ts0610));
        $this->assertNull($this->dispatcher->cron('10,30,50 * * * *', $ts0620));

        $this->assertSame($this->dispatcher, $this->dispatcher->cron('@15minutes', $ts0600));
        $this->assertNull($this->dispatcher->cron('@15minutes', $ts0610));
        $this->assertSame($this->dispatcher, $this->dispatcher->cron('20,40 * * * *', $ts0620));
        $this->assertSame($this->dispatcher, $this->dispatcher->cron('0-20 5-7 * * 1-5', $ts0610));
        $this->assertNull($this->dispatcher->cron('25-50 5-7 * * 1-5', $ts0610));

        $this->expectException(UnexpectedValueException::class);
        $this->dispatcher->cron('invalid cron string', $ts0600);
    }

    #[TestDox('QueueManagerCommand и QueueMixin: дедуплицируют push, читают RESP-массивы и управляют очередью (--status, --switch, --remove, --clear)')]
    public function testQueueManagementLifecycle(): void
    {
        $output = new BufferedOutput();

        $this->assertTrue($this->dispatcher->push('fl:agy'));
        $this->assertFalse($this->dispatcher->push('fl:agy'));
        $this->assertTrue($this->dispatcher->push('hh:agy'));
        $this->assertSame(['fl:agy', 'hh:agy'], $this->dispatcher->pending());

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--switch' => true]), $output));
        $this->assertFalse($this->dispatcher->state());
        $this->assertStringContainsString('Очередь поставлена на паузу', $output->fetch());

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--status' => true]), $output));
        $statusText = $output->fetch();
        $this->assertStringContainsString('На паузе', $statusText);
        $this->assertStringContainsString('1. fl:agy', $statusText);
        $this->assertStringContainsString('2. hh:agy', $statusText);

        $this->assertSame(Command::FAILURE, $this->command->run(new ArrayInput(['--remove' => null]), $output));
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--remove' => 'fl:agy']), $output));
        $this->assertSame(['hh:agy'], $this->dispatcher->pending());

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--switch' => true]), $output));
        $this->assertTrue($this->dispatcher->state());

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--clear' => true]), $output));
        $this->assertSame([], $this->dispatcher->pending());
    }

    #[TestDox('WorkDispatcher: выполняет очередь строго последовательно (FIFO), обновляет active и останавливается при паузе')]
    public function testWorkDispatcherSequentialExecutionAndPause(): void
    {
        $executed = [];
        $dispatcher = $this->dispatcher;

        $agent = new class($this->container, $this->fs, $executed, $dispatcher) extends AgyAgent
        {
            public function __construct(DefinitionContainerInterface $container, Filesystem $fs, private array &$executed, private WorkDispatcher $dispatcher)
            {
                parent::__construct($container, $fs);
            }

            protected function process(string $prompt): Process
            {
                $this->executed[] = $this->dispatcher->redis->command(['GET', $this->dispatcher->config['keys']['active']]);

                if(count($this->executed) === 1) $this->dispatcher->redis->command(['SET', $this->dispatcher->config['keys']['paused'], '1']);

                return new Process(['php', '-r', 'echo "Done";']);
            }
        };

        $this->container->add(AgyAgent::class, $agent);

        $this->dispatcher->cron('0 */6 * * *', mktime(6, 0, 0, 10, 29, 2026))?->push('openrouter:agy');
        $this->dispatcher->push('invalid:agy');
        $this->dispatcher->push('fl:agy');
        $this->dispatcher->push('hh:agy');
        $this->assertSame(['openrouter:agy', 'invalid:agy', 'fl:agy', 'hh:agy'], $this->dispatcher->pending());

        $output = new BufferedOutput();
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--work' => true]), $output));

        $this->assertSame(['openrouter:agy'], $executed);
        $this->assertNull($this->redis->command(['GET', $this->dispatcher->config['keys']['active']]));
        $this->assertSame(['invalid:agy', 'fl:agy', 'hh:agy'], $this->dispatcher->pending());

        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--work' => true]), $output));
        $this->assertStringContainsString('Очередь поставлена на паузу', $output->fetch());
        $this->assertSame(['invalid:agy', 'fl:agy', 'hh:agy'], $this->dispatcher->pending());

        $this->redis->unlock($this->dispatcher->config['keys']['paused']);
        $this->assertSame(Command::SUCCESS, $this->command->run(new ArrayInput(['--work' => true]), $output));
        $this->assertStringContainsString('Ошибка выполнения задачи invalid:agy', $output->fetch());
        $this->assertSame(['openrouter:agy', 'fl:agy', 'hh:agy'], $executed);
        $this->assertSame([], $this->dispatcher->pending());
    }
}
