<?php namespace Magistrale\Inflectors;

use Magistrale\Contracts\{CallbackInterface, OperableInterface};

class CallbackInflector extends AbstractInflector
{
    public function __invoke(OperableInterface $object): void
    {
        $callback = clone $this->container()->get(CallbackInterface::class);

        if($operator = $this->operators($object)) $callback->container = $operator($object);

        $object->setCallback($callback);
    }
}
