<?php namespace App\Tools;

use App\Models\OpenRouterModel;
use InvalidArgumentException;

final class MarkPublishedTool
{
    public function markPublished(string $modelId): string
    {
        if(!$modelId) throw new InvalidArgumentException('$modelId must not be empty');

        return OpenRouterModel::where('model_id', $modelId)->update(['published' => true]) ? 'success' : 'not found';
    }
}
