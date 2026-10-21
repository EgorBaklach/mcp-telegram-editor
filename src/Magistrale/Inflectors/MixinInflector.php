<?php namespace Magistrale\Inflectors;

use Framework\Inflectors\InflectorAbstract;
use Magistrale\Mixins\MacroableInterface;

class MixinInflector extends InflectorAbstract
{
    public function __invoke(MacroableInterface $object): void
    {
        $container = $this->container(); foreach($container->get('mixins') as $target => $mixin) if($object instanceof $target) $object::mixin($container->get($mixin));
    }
}
