<?php namespace Magistrale\Inflectors;

use Framework\Inflectors\InflectorAbstract;
use Magistrale\Dispatchers\Source\AbstractDispatcher;

class SourceInflector extends InflectorAbstract
{
    public function __invoke(AbstractDispatcher $dispatcher): void
    {
        $container = $this->container();

        foreach($container->get('sources') as $target => $client)
        {
            if($dispatcher instanceof $target)
            {
                $dispatcher->setClient($container->get($client));
            }
        }
    }
}
