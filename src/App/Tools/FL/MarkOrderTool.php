<?php namespace App\Tools\FL;

use App\Models\FLOrder;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;

final class MarkOrderTool
{
    public function __construct(private readonly Capsule $capsule) {}

    public function mark(int $projectId, bool $published): string
    {
        if($projectId <= 0) throw new InvalidArgumentException('$projectId must be positive');

        return FLOrder::where('project_id', $projectId)->update(['published' => $published]) ? 'success' : 'not found';
    }
}
