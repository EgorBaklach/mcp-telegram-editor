<?php namespace Magistrale\Dispatchers\OpenRouter;

use Magistrale\Dispatchers\ResultInterface;
use App\Models\OpenRouterModel;
use Throwable;

class SyncDispatcher extends AbstractDispatcher implements ResultInterface
{
    private array $results = [];

    public function dispatch(mixed $payload = null): bool
    {
        $this->results = [];

        try
        {
            $response = $this->client->getModels(); if($response->getStatusCode() !== 200) return false;

            $data = json_decode((string) $response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            $existing = OpenRouterModel::query()->select(['model_id', 'released_at'])->pluck('released_at', 'model_id')->all();

            foreach($data['data'] ?? [] as $model)
            {
                if(!isset($model['pricing']['prompt'], $model['pricing']['completion']) || $model['pricing']['prompt'] !== '0' || $model['pricing']['completion'] !== '0') continue;

                $releasedAt = isset($model['created']) ? date('Y-m-d H:i:s', $model['created']) : null;

                if(array_key_exists($model['id'], $existing))
                {
                    if($releasedAt && $existing[$model['id']] === null) OpenRouterModel::where('model_id', $model['id'])->whereNull('released_at')->update(['released_at' => $releasedAt]);
                    
                    continue;
                }

                OpenRouterModel::create([
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'accessible' => 'free',
                    'context_length' => $model['context_length'] ?? null,
                    'modality' => $model['architecture']['modality'] ?? null,
                    'released_at' => $releasedAt,
                ]);

                $this->results[] = [
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'context_length' => $model['context_length'] ?? null,
                    'modality' => $model['architecture']['modality'] ?? null,
                    'description' => $model['description'] ?? '',
                    'has_reasoning' => isset($model['reasoning']),
                    'has_tool_use' => in_array('tools', $model['supported_parameters'] ?? [], true),
                ];
            }

            $this->logger->info(sprintf('OpenRouter sync: %d new', count($this->results)));

            return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('OpenRouter sync failed: '.(string) $e);
        }

        return false;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
