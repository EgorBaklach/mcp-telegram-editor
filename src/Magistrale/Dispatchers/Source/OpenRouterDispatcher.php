<?php namespace Magistrale\Dispatchers\Source;

use App\Models\OpenRouterModel;
use Throwable;

class OpenRouterDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        $this->results = [];

        try
        {
            if(!$models = $this->callback->fetch('models')) return false;

            $existing = OpenRouterModel::query()->select(['model_id', 'released_at'])->pluck('released_at', 'model_id')->all();

            foreach($models as $model)
            {
                if(!$this->isFreeModel($model) || $this->isExisting($model, $existing, $released_at = $this->formatReleasedAt($model))) continue;

                OpenRouterModel::create([
                    'model_id' => $model['id'],
                    'name' => $model['name'],
                    'accessible' => 'free',
                    'context_length' => $model['context_length'] ?? null,
                    'modality' => $model['architecture']['modality'] ?? null,
                    'released_at' => $released_at,
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
}
