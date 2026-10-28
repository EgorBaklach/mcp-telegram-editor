<?php namespace App\Tools\HH;

use App\Models\HHVacancy;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;

final class MarkVacancyTool
{
    public function __construct(private readonly Capsule $capsule) {}

    public function mark(int $vacancyId, bool $published): string
    {
        if($vacancyId <= 0) throw new InvalidArgumentException('$vacancyId must be positive');

        return HHVacancy::where('vacancy_id', $vacancyId)->update(['published' => $published]) ? 'success' : 'not found';
    }
}
