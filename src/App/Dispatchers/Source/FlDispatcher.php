<?php namespace App\Dispatchers\Source;

use App\Models\FlOrder;
use Throwable;

class FlDispatcher extends AbstractDispatcher
{
    private array $orders = [];

    public function dispatch(mixed $payload = null): bool
    {
        $this->orders = []; $this->results = [];

        try
        {
            if(!$this->callback->fetch('native') && !$this->callback->fetch('rss')) return false;

            $existing = FlOrder::query()->select(['project_id', 'hash'])->pluck('project_id', 'hash')->all();

            foreach(array_reverse($this->orders) as $order)
            {
                if(isset($existing[$order['hash']])) continue; $existing[$order['hash']] = $order['project_id'];

                if($description = $this->callback->fetch('description', $order))
                {
                    $this->results[] = [
                        'project_id' => $order['project_id'],
                        'title' => $order['title'],
                        'link' => $order['link'],
                        'budget' => $order['budget'],
                        'description' => $description,
                    ];
                }

                FlOrder::create($order + [
                    'description' => $description ?? $order['preview'],
                    'published' => $description ? null : false,
                ]);
            }

            $this->logger->info(sprintf('FL sync: %d new', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('FL sync failed: '.(string) $e); $this->results = [];
        }

        return false;
    }
}
