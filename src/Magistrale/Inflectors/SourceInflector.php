<?php namespace Magistrale\Inflectors;

use App\Dispatchers\Source\AbstractDispatcher;

class SourceInflector extends AbstractInflector
{
    public function __invoke(AbstractDispatcher $dispatcher): void
    {
        if($source = $this->sources($dispatcher)) $dispatcher->setClient($source);
    }
}
