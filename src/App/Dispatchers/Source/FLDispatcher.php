<?php namespace App\Dispatchers\Source;

use App\Models\FLOrder;
use RuntimeException;
use Throwable;

class FLDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        $this->results = []; $this->upsert = [];

        try
        {
            FLOrder::query()->where('created_at', '<', date('Y-m-d H:i:s', strtotime($this->client->config['criteria']['ttl'])))->delete();

            if(!$this->callback->fetch('native') && !$this->callback->fetch('rss')) throw new RuntimeException('FL.ru catalog and RSS fetch failed');

            foreach(FLOrder::ingest(array_reverse($this->upsert), 'project_id') as $id)
            {
                if(!$description = $this->callback->fetch('description', $order = $this->upsert[$id])) continue;

                $this->results[] = ['description' => $description, 'published' => null] + $order;
            }

            FLOrder::upsert($this->results, ['project_id'], ['description', 'published']);

            $this->logger->info(sprintf('FL sync: %d new', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('FL sync failed: '.(string) $e); $this->observer->update($e); $this->results = [];
        }

        return false;
    }
}
