<?php namespace Magistrale\Providers;

use Closure;
use Framework\Providers\AbstractProvider;
use Magistrale\Contracts\CallbackInterface;
use ReflectionFunction;
use ReflectionParameter;

final class CallbackProvider extends AbstractProvider
{
    protected array $provides = [
        CallbackInterface::class
    ];

    public function register(): void
    {
        $this->container()->add(CallbackInterface::class, fn() => new class implements CallbackInterface
        {
            /** @var array<string, Closure|Closure[]> */
            public array $container = [];

            public function __get(string $name): Closure
            {
                $callback = $this->container[$name] ?? fn() => null; return is_array($callback) ? $this->pipe($callback) : $callback;
            }

            public function __call(string $operator, array $arguments): mixed
            {
                return $this->handle($operator, ...$arguments);
            }

            private function handle(string $operator, Closure|string $entry, mixed ...$arguments): mixed
            {
                return call_user_func($entry instanceof Closure ? $this->reflect($operator, new ReflectionFunction($entry)) : $this->{$operator.'.'.$entry}, ...$arguments);
            }

            private function reflect(string $operator, ReflectionFunction $reflection): Closure
            {
                return function() use ($operator, $reflection): mixed
                {
                    return $reflection->invoke(...array_map(fn(ReflectionParameter $param): Closure => $this->{$operator.'.'.$param->getName()}, $reflection->getParameters()));
                };
            }

            private function pipe(array $pipes): Closure
            {
                return function(mixed ...$arguments) use ($pipes): mixed
                {
                    foreach($pipes as $pipe)
                    {
                        if(!$result = $pipe(...$arguments)) return null; $arguments = is_array($result) ? $result : [$result];
                    }

                    return $result ?? null;
                };
            }
        });
    }
}
