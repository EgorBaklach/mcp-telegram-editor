<?php namespace App\Tools\HH;

use Magistrale\Contracts\ObservableInterface;
use Magistrale\Traits\ObservableTrait;
use Mcp\Capability\Attribute\Schema;

final class NotifyVacancyTool implements ObservableInterface
{
    use ObservableTrait;

    /** @param array{match: int, title: string, link: string, rate: string, summary: string, company: string, address: string, schedule: string, salary: string, proposal: string} $payload */
    public function notify(#[Schema(required: ['match', 'title', 'link', 'rate', 'summary'])] array $payload): string
    {
        return $this->observer->update('hh', $payload) ? 'success' : 'failed';
    }
}
