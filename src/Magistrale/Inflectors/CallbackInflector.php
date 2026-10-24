<?php namespace Magistrale\Inflectors;

use Framework\Inflectors\InflectorAbstract;
use Magistrale\Contracts\{CallbackInterface, OperableInterface};

class CallbackInflector extends InflectorAbstract
{
    public function __invoke(OperableInterface $object): void
    {
        $container = $this->container();

        foreach($container->get('operators') as $target => $operator)
        {
            if($object instanceof $target)
            {
                $callback = clone $container->get(CallbackInterface::class); 

                $callback->container = $container->get($operator)($object); 

                $object->setCallback($callback);
            }
        }
    }
}