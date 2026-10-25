<?php namespace App\Dispatchers\Source;

use App\Models\OpenRouterModel;
use Magistrale\Contracts\CallbackInterface;
use Throwable;

class OpenRouterDispatcher extends AbstractDispatcher
{
    public function setCallback(CallbackInterface $callback): void
    {
        $callback->container['openrouter.isFreeModel'] = fn(?array $pricing) => $pricing['prompt'] === '0' && $pricing['completion'] === '0'; $this->callback = $callback;
    }

    public function dispatch(mixed $payload = null): bool
    {
        $this->results = [];

        try
        {
            if(!$response = $this->client->getNat64Models() ?? $this->client->getModels()) return false;

            if(!array_key_exists('data', $data = json_decode((string) $response->getBody(), true, 512, JSON_BIGINT_AS_STRING))) return false;

            $existing = OpenRouterModel::query()->select(['id', 'model_id'])->pluck('id', 'model_id')->all();

            foreach($data['data'] as $model)
            {
                if(!$this->callback->openrouter('isFreeModel', $model['pricing']) || isset($existing[$model['id']])) continue; $existing[$model['id']] = true;

                OpenRouterModel::create([
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'accessible' => 'free',
                    'context_length' => $model['context_length'],
                    'modality' => $model['architecture']['modality'],
                    'released_at' => isset($model['created']) ? date('Y-m-d H:i:s', $model['created']) : null,
                ]);

                $this->results[] = [
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'context_length' => $model['context_length'],
                    'modality' => $model['architecture']['modality'],
                    'description' => $model['description'],
                    'has_reasoning' => isset($model['reasoning']),
                    'has_tool_use' => in_array('tools', $model['supported_parameters'], true),
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
}
