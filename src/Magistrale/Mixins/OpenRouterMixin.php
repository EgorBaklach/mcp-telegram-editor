<?php namespace Magistrale\Mixins;

use App\Models\OpenRouterModel;
use Closure;
use Psr\Http\Message\ResponseInterface;

class OpenRouterMixin
{
    public function decodeModels(): Closure
    {
        return function(ResponseInterface $response): array
        {
            $data = json_decode((string) $response->getBody(), true, 512, JSON_BIGINT_AS_STRING); return $data['data'] ?? [];
        };
    }

    public function isFreeModel(): Closure
    {
        return function(array $model): bool
        {
            return isset($model['pricing']['prompt'], $model['pricing']['completion']) && $model['pricing']['prompt'] === '0' && $model['pricing']['completion'] === '0';
        };
    }

    public function formatReleasedAt(): Closure
    {
        return function(array $model): ?string
        {
            return isset($model['created']) ? date('Y-m-d H:i:s', $model['created']) : null;
        };
    }

    public function isExisting(): Closure
    {
        return function(array $model, array $existing, ?string $released_at): bool
        {
            if(!array_key_exists($model['id'], $existing)) return false;

            if($released_at && !$existing[$model['id']]) OpenRouterModel::where('model_id', $model['id'])->whereNull('released_at')->update(compact('released_at'));

            return true;
        };
    }
}
