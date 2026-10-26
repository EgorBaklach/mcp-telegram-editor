<?php namespace Magistrale\Traits;

use Magistrale\Contracts\ObserverInterface;

trait ObservableTrait
{
    public ObserverInterface $observer;

    public function setObserver(ObserverInterface $observer): void
    {
        $this->observer = $observer;
    }
}
