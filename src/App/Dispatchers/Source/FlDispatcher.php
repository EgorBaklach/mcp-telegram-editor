<?php namespace App\Dispatchers\Source;

use App\Models\FlOrder;
use Throwable;

class FlDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        $this->results = [];

        try
        {
            if(!$orders = $this->callback->fetch('native') ?? $this->callback->fetch('rss')) return false;

            $existing = FlOrder::query()->select(['project_id', 'hash'])->pluck('hash', 'project_id')->all();

            foreach($orders as $order)
            {
                if($this->isExisting($order, $existing)) continue;

                $description = $this->callback->fetch('description', $order) ?? $order['preview'];

                FlOrder::create([
                    'project_id' => $order['project_id'],
                    'hash' => $order['hash'],
                    'title' => $order['title'],
                    'link' => $order['link'],
                    'budget' => $order['budget'],
                    'description' => $description,
                    'published' => $this->isSmallTask($order, $description) ? false : null,
                ]);

                $existing[$order['project_id']] = $order['hash'];
            }

            foreach(FlOrder::query()->whereNull('published')->select(['project_id', 'title', 'link', 'budget', 'description'])->orderBy('id')->cursor() as $row)
            {
                $this->results[] = [
                    'project_id' => $row->project_id,
                    'title' => $row->title,
                    'link' => $row->link,
                    'budget' => $row->budget,
                    'description' => $row->description,
                ];
            }

            $this->logger->info(sprintf('FL sync: %d pending', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('FL sync failed: '.(string) $e);
        }

        return false;
    }
}
