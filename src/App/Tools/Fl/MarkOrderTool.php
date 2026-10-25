<?php namespace App\Tools\Fl;

use App\Models\FlOrder;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;

final class MarkOrderTool
{
    public function __construct(private readonly Capsule $capsule) {}

    public function mark(int $projectId, bool $published): string
    {
        if($projectId <= 0) throw new InvalidArgumentException('$projectId must be positive');

        return FlOrder::where('project_id', $projectId)->update(['published' => $published]) ? 'success' : 'not found';
    }
}
