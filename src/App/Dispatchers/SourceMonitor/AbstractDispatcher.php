<?php namespace App\Dispatchers\SourceMonitor;

use Cli\Commands\SourceMonitorCommand;
use Magistrale\Contracts\{AgentInterface, DispatcherInterface, ObservableInterface, RedisInterface};
use Magistrale\Traits\ObservableTrait;
use RuntimeException;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface, ObservableInterface
{
    use ObservableTrait;

    protected SourceMonitorCommand $command;

    protected const source = '';

    protected const lock = '';

    public function __construct(protected readonly RedisInterface $redis) {}

    public function build(SourceMonitorCommand $command): self
    {
        $this->command = $command; return $this;
    }

    public function dispatch(mixed $payload = null): bool
    {
        $agents = $this->command->container->get('agents');

        if(!$payload || !isset($agents['drivers'][$payload]))
        {
            return $this->command->logger->error('Укажите доступного AI-агента ('.implode(', ', array_keys($agents['drivers'])).') через --'.static::source.'=ИМЯ_АГЕНТА');
        }

        if($this->redis->locked(static::lock))
        {
            return $this->command->logger->comment('Источник '.static::source.' уже обрабатывается (lock: '.static::lock.'). Пропуск.');
        }

        /** @var AgentInterface $agent */ $agent = $this->command->container->get($agents['drivers'][$payload]);

        try
        {
            $this->command->logger->comment('Запуск AI-агента ('.$payload.') для источника '.static::source.'...');

            $agent->prepare(static::source);

            if(!$agent->run('Активируй скилл source-monitor и выполни pipeline для источника: '.static::source))
            {
                throw new RuntimeException('Ошибка выполнения агента ('.static::source.'): '.($agent->error() ?: $agent->output()));
            }

            if($output = $agent->output()) $this->command->logger->comment($output);

            return $this->command->logger->info('Мониторинг источника '.static::source.' успешно завершен.');
        }
        catch(Throwable $e)
        {
            $this->observer->update($e);

            return $this->command->logger->error('Сбой запуска агента ('.static::source.'): '.$e->getMessage());
        }
        finally
        {
            $agent->cleanup(); $this->redis->unlock(static::lock);
        }
    }
}
