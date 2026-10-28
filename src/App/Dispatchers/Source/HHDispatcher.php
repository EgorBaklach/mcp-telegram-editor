<?php namespace App\Dispatchers\Source;

use App\Models\HHVacancy;
use Magistrale\Contracts\CallbackInterface;
use RuntimeException;
use Throwable;

class HHDispatcher extends AbstractDispatcher
{   
    public function dispatch(mixed $payload = null): bool
    {
        $this->results = []; $this->upsert = [];

        try
        {
            HHVacancy::query()->where('created_at', '<', date('Y-m-d H:i:s', strtotime($this->client->config['criteria']['ttl'])))->delete();

            if(!$this->callback->fetch('native') && !$this->callback->fetch('rss')) throw new RuntimeException('HH.ru catalog and RSS fetch failed');

            foreach(HHVacancy::ingest(array_reverse($this->upsert), 'vacancy_id') as $id)
            {
                if(!$description = $this->callback->fetch('description', $vacancy = $this->upsert[$id])) continue;

                $this->results[] = ['description' => $description, 'published' => null] + $vacancy;
            }

            HHVacancy::upsert($this->results, ['vacancy_id'], ['description', 'published']);

            $this->logger->info(sprintf('HH sync: %d new', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('HH sync failed: '.(string) $e); $this->observer->update($e); $this->results = [];
        }

        return false;
    }
}
