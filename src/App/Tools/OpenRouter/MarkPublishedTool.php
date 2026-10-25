<?php namespace App\Tools\OpenRouter;

use App\Models\OpenRouterModel;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;

final class MarkPublishedTool
{
    public function __construct(private readonly Capsule $capsule) {}

    public function mark(string $modelId): string
    {
        if(!$modelId) throw new InvalidArgumentException('$modelId must not be empty');

        return OpenRouterModel::where('model_id', $modelId)->update(['published' => true]) ? 'success' : 'not found';
    }
}
