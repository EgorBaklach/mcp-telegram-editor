<?php namespace App\Tools\FL;

use Magistrale\Contracts\ObservableInterface;
use Magistrale\Traits\ObservableTrait;
use Mcp\Capability\Attribute\Schema;

final class NotifyOrderTool implements ObservableInterface
{
    use ObservableTrait;

    /** @param array{match: int, title: string, link: string, rate: string, summary: string, budget: string, proposal: string} $payload */
    public function notify(#[Schema(required: ['match', 'title', 'link', 'rate', 'summary'])] array $payload): string
    {
        return $this->observer->update('fl', $payload) ? 'success' : 'failed';
    }
}
