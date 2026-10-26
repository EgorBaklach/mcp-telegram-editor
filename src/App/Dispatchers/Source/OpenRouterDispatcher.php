<?php namespace App\Dispatchers\Source;

use App\Models\OpenRouterModel;
use Magistrale\Contracts\CallbackInterface;
use RuntimeException;
use Throwable;

class OpenRouterDispatcher extends AbstractDispatcher
{
    public function setCallback(CallbackInterface $callback): void
    {
        $callback->container['openrouter.free'] = fn(array $pricing) => $pricing['prompt'] === '0' && $pricing['completion'] === '0'; $this->callback = $callback;
    }

    public function dispatch(mixed $payload = null): bool
    {
        $this->results = []; $this->upsert = [];

        try
        {
            if(!$response = $this->client->nat64() ?? $this->client->models()) throw new RuntimeException('OpenRouter API request failed');

            $result = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            foreach($result['data'] as $model)
            {
                if(!$this->callback->openrouter('free', $model['pricing'])) continue;

                $this->upsert[$model['id']] = [
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'accessible' => 'free',
                    'context_length' => $model['context_length'],
                    'modality' => $model['architecture']['modality'],
                    'released_at' => isset($model['created']) ? date('Y-m-d H:i:s', $model['created']) : null,
                ];
            }

            $this->results = OpenRouterModel::ingest(array_reverse($this->upsert), 'model_id', fn(string $id) => $this->upsert[$id]);

            $this->logger->info(sprintf('OpenRouter sync: %d new', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('OpenRouter sync failed: '.(string) $e); $this->observer->update($e); $this->results = [];
        }

        return false;
    }
}
