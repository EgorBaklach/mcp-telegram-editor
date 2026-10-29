<?php namespace Magistrale\Inflectors;

use Framework\Inflectors\AbstractInflector as BaseInflector;
use Magistrale\Clients\AbstractClient;
use Magistrale\Contracts\{MixinInterface, ObserverInterface, OperatorInterface};

/**
 * @method array<OperatorInterface>|null helpers(object $object)
 * @method MixinInterface|null mixins(object $object)
 * @method ObserverInterface|null observers(object $object)
 * @method OperatorInterface|null operators(object $object)
 * @method AbstractClient|null sources(object $object)
 */
abstract class AbstractInflector extends BaseInflector
{
    public function __call(string $definition, array $arguments): object|array|null
    {
        $object = array_shift($arguments); $container = $this->container(); $items = $container->get($definition);

        if($target = $items[$object::class] ?? $items[get_parent_class($object)] ?? null)
        {
            return is_array($target) ? array_map($container->get(...), $target) : $container->get($target);
        }

        return null;
    }
}
