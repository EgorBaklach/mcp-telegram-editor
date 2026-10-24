<?php namespace Magistrale\Inflectors;

use App\Clients\AbstractClient;
use Framework\Inflectors\AbstractInflector as BaseInflector;

/**
 * @method object|null mixins(object $object)
 * @method callable|null operators(object $object)
 * @method AbstractClient|null sources(object $object)
 */
abstract class AbstractInflector extends BaseInflector
{
    public function __call(string $definition, array $arguments): ?object
    {
        [$object] = $arguments; $container = $this->container(); $items = $container->get($definition);

        if($class = $items[$object::class] ?? $items[get_parent_class($object)] ?? null)
        {
            return $container->get($class);
        }

        return null;
    }
}
