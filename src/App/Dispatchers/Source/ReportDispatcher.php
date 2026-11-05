<?php namespace App\Dispatchers\Source;

use Magistrale\Contracts\CallbackInterface;
use RuntimeException;
use Throwable;

class ReportDispatcher extends AbstractDispatcher
{
    public function setCallback(CallbackInterface $callback): void
    {
        $callback->container['report.funnel'] = fn(string $model): array => (array) $model::query()->toBase()
            ->selectRaw('COUNT(*) as total, COUNT(*) FILTER (WHERE published IS TRUE) as published')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->first();

        $this->callback = $callback;
    }

    public function dispatch(mixed $payload = null): bool
    {
        try
        {
            $sources = [];

            foreach($payload as $source => $config) $sources[$source] = $this->callback->report('funnel', $config['model']);

            if(!$this->observer->update('report', compact('sources'))) throw new RuntimeException('Failed to send daily report to admin');

            $this->logger->info('Daily source-monitor report sent successfully.');

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('Daily report failed: '.(string) $e); $this->observer->update($e);
        }

        return false;
    }
}