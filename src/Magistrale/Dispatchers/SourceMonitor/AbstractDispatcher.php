<?php namespace Magistrale\Dispatchers\SourceMonitor;

use Cli\Commands\SourceMonitorCommand;
use Magistrale\Agents\AgentInterface;
use Magistrale\Clients\Redis;
use Magistrale\Dispatchers\DispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected SourceMonitorCommand $command;

    protected const sourceName = '';

    protected const lockKey = '';

    public function __construct(protected readonly Redis $redis, protected readonly Filesystem $fs) {}

    public function build(SourceMonitorCommand $command): self
    {
        $this->command = $command; return $this;
    }

    public function dispatch(mixed $payload = null): bool
    {
        $agents = $this->command->container->get('agents'); $agentKey = (string) ($payload ?: $agents['default']);

        if(!isset($agents['drivers'][$agentKey]))
        {
            $this->command->logger->error("Неизвестный AI-агент: {$agentKey}. Доступные: ".implode(', ', array_keys($agents['drivers']))); return false;
        }

        if($this->redis->isLocked(static::lockKey))
        {
            return $this->command->logger->comment('Источник '.static::sourceName.' уже обрабатывается (lock: '.static::lockKey.'). Пропуск.');
        }

        /** @var AgentInterface $agent */ $agent = $this->command->container->get($agents['drivers'][$agentKey]);

        try
        {
            $this->command->logger->comment("Запуск AI-агента ({$agentKey}) для источника ".static::sourceName.'...');

            $agent->prepare(static::sourceName);

            if(!$agent->run('Активируй скилл source-monitor и выполни pipeline для источника: '.static::sourceName))
            {
                $this->command->logger->error('Ошибка выполнения агента ('.static::sourceName.'): '.($agent->getErrorOutput() ?: $agent->getOutput())); return false;
            }

            if($output = $agent->getOutput()) $this->command->logger->comment($output);

            return $this->command->logger->info('Мониторинг источника '.static::sourceName.' успешно завершен.');
        }
        catch(Throwable $e)
        {
            $this->command->logger->error('Сбой запуска агента ('.static::sourceName."): {$e->getMessage()}"); return false;
        }
        finally
        {
            $agent->cleanup(); $this->fs->remove(glob(sys_get_temp_dir().'/mcp-sessions/*') ?: []); $this->redis->releaseLock(static::lockKey);
        }
    }
}
