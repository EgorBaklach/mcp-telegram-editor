<?php namespace App\Tools\FL;

use Magistrale\Contracts\ObservableInterface;
use Magistrale\Traits\ObservableTrait;

final class NotifyOrderTool implements ObservableInterface
{
    use ObservableTrait;

    /** @param array{match: int, title: string, link: string, rate: string, summary: string} $payload */
    public function notify(array $payload): string
    {
        return $this->observer->update('fl', $payload) ? 'success' : 'failed';
    }
}
