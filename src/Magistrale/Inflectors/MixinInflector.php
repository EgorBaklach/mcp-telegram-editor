<?php namespace Magistrale\Inflectors;

use Magistrale\Contracts\MacroableInterface;

class MixinInflector extends AbstractInflector
{
    public function __invoke(MacroableInterface $object): void
    {
        if($mixin = $this->mixins($object)) $object::mixin($mixin);
    }
}
