<?php namespace Magistrale\Dispatchers\Queue;

use Framework\Contracts\Console\ConsoleInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;

class WorkDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$this->state()) return $this->logger->comment('Очередь поставлена на паузу.');

        foreach($this->config['schedules'] as $job => $expression) $this->cron($expression)?->push($job);

        while($job = $this->redis->command(['LPOP', $this->config['keys']['pending']]))
        {
            $this->redis->command(['SET', $payload['lock'], '1', 'EX', $this->config['ttl']]);
            $this->redis->command(['SET', $this->config['keys']['active'], $job, 'EX', $this->config['ttl']]);

            try
            {
                [$option, $agent] = array_pad(explode(':', $job, 2), 2, null);

                $this->container->get(ConsoleInterface::class)->get($this->config['command'])->run(new ArrayInput(['--'.$option => $agent]), $this->logger->getOutput());
            }
            catch(Throwable $e)
            {
                $this->observer->update($e); $this->logger->error('Ошибка выполнения задачи '.$job.': '.$e->getMessage());
            }
            finally
            {
                $this->redis->unlock($this->config['keys']['active']);
            }

            if(!$this->state()) break;
        }

        return true;
    }
}
