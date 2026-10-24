<?php namespace Magistrale\Inflectors;

use Magistrale\Contracts\{CallbackInterface, OperableInterface};

class CallbackInflector extends AbstractInflector
{
    public function __invoke(OperableInterface $object): void
    {
        if($operator = $this->operators($object))
        {
            $callback = clone $this->container()->get(CallbackInterface::class); $callback->container = $operator($object); $object->setCallback($callback);
        }
    }
}
