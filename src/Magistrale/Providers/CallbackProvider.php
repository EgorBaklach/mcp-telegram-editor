<?php namespace Magistrale\Providers;

use Closure;
use Framework\Providers\ProviderAbstract;
use Magistrale\Contracts\CallbackInterface;

final class CallbackProvider extends ProviderAbstract
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
                return call_user_func($this->{$operator.'.'.array_shift($arguments)}, ...$arguments);
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
