<?php namespace Magistrale\Dispatchers\SourceMonitor;

use Cli\Commands\SourceMonitorCommand;
use Magistrale\Agents\AgentInterface;
use Magistrale\Clients\Redis;
use Magistrale\Dispatchers\DispatcherInterface;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected SourceMonitorCommand $command;

    protected const sourceName = '';

    protected const lockKey = '';

    public function __construct(protected readonly Redis $redis) {}

    public function build(SourceMonitorCommand $command): self
    {
        $this->command = $command; return $this;
    }

    public function dispatch(mixed $payload = null): bool
    {
        $agents = $this->command->container->get('agents');

        if(!$payload || !isset($agents['drivers'][$payload]))
        {
            return $this->command->logger->error('Укажите доступного AI-агента ('.implode(', ', array_keys($agents['drivers'])).') через --'.static::sourceName.'=ИМЯ_АГЕНТА');
        }

        if($this->redis->isLocked(static::lockKey))
        {
            return $this->command->logger->comment('Источник '.static::sourceName.' уже обрабатывается (lock: '.static::lockKey.'). Пропуск.');
        }

        /** @var AgentInterface $agent */ $agent = $this->command->container->get($agents['drivers'][$payload]);

        try
        {
            $this->command->logger->comment("Запуск AI-агента ({$payload}) для источника ".static::sourceName.'...');

            $agent->prepare(static::sourceName);

            if(!$agent->run('Активируй скилл source-monitor и выполни pipeline для источника: '.static::sourceName))
            {
                return $this->command->logger->error('Ошибка выполнения агента ('.static::sourceName.'): '.($agent->getErrorOutput() ?: $agent->getOutput()));
            }

            if($output = $agent->getOutput()) $this->command->logger->comment($output);

            return $this->command->logger->info('Мониторинг источника '.static::sourceName.' успешно завершен.');
        }
        catch(Throwable $e)
        {
            return $this->command->logger->error('Сбой запуска агента ('.static::sourceName."): {$e->getMessage()}");
        }
        finally
        {
            $agent->cleanup(); $this->redis->releaseLock(static::lockKey);
        }
    }
}
