<?php namespace App\Tools\HH;

use Magistrale\Contracts\ObservableInterface;
use Magistrale\Traits\ObservableTrait;

final class NotifyVacancyTool implements ObservableInterface
{
    use ObservableTrait;

    /** @param array{match: int, title: string, link: string, rate: string, summary: string} $payload */
    public function notify(array $payload): string
    {
        return $this->observer->update('hh', $payload) ? 'success' : 'failed';
    }
}
