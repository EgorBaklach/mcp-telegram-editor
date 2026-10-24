<?php namespace Magistrale\Traits;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

trait MacroableTrait
{
    protected static array $macros = [];

    public static function mixin(object $mixin, bool $replace = true): void
    {
        foreach((new ReflectionClass($mixin))->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method)
        {
            if($replace || !static::hasMacro($method->name)) static::$macros[static::class][$method->name] = $method->invoke($mixin);
        }
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[static::class][$name]);
    }

    public function __call(string $method, array $parameters): mixed
    {
        if(!static::hasMacro($method)) throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));

        $macro = static::$macros[static::class][$method]; return ($macro instanceof Closure ? $macro->bindTo($this, static::class) : $macro)(...$parameters);
    }
}
