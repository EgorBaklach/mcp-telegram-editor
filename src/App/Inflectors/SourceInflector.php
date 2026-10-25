<?php namespace App\Inflectors;

use App\Dispatchers\Source\AbstractDispatcher;
use Magistrale\Inflectors\AbstractInflector;

class SourceInflector extends AbstractInflector
{
    public function __invoke(AbstractDispatcher $dispatcher): void
    {
        if($source = $this->sources($dispatcher)) $dispatcher->setClient($source);
    }
}
