<?php namespace Cli\Commands;

use Magistrale\Contracts\{AgentInterface, ObservableInterface};
use Magistrale\Traits\ObservableTrait;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'source:monitor', description: 'Запускает мониторинг внешних источников данных через AI-агента (source-monitor)')]
class SourceMonitorCommand extends AbstractCommand implements ObservableInterface
{
    use ObservableTrait;

    public function handle(string $source, array $config, mixed $payload = null): bool
    {
        $drivers = $this->callback->array('get', $this->container->get('agents'), 'drivers');

        if(!$payload || !isset($drivers[$payload]))
        {
            return $this->logger->error('Укажите доступного AI-агента ('.implode(', ', array_keys($drivers)).') через --'.$source.'=ИМЯ_АГЕНТА');
        }

        /** @var AgentInterface $agent */ $agent = $this->container->get($drivers[$payload]);

        try
        {
            $this->logger->comment('Запуск AI-агента ('.$payload.') для источника '.$source.'...');

            $agent->prepare($source);

            if(!$agent->run('Активируй скилл source-monitor и выполни pipeline для источника: '.$source))
            {
                throw new RuntimeException('Ошибка выполнения агента ('.$source.'): '.($agent->error() ?: $agent->output()));
            }

            if($output = $agent->output()) $this->logger->comment($output);

            return $this->logger->info('Мониторинг источника '.$source.' успешно завершен.');
        }
        catch(Throwable $e)
        {
            $this->observer->update($e); return $this->logger->error('Сбой запуска агента ('.$source.'): '.$e->getMessage());
        }
        finally
        {
            $agent->cleanup();
        }
    }
}
