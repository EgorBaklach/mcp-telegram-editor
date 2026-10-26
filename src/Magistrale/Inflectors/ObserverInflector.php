<?php namespace Magistrale\Inflectors;

use Magistrale\Contracts\ObservableInterface;

class ObserverInflector extends AbstractInflector
{
    public function __invoke(ObservableInterface $object): void
    {
        if($observer = $this->observers($object)) $object->setObserver($observer);
    }
}
