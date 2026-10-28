<?php namespace Magistrale\Helpers;

use Magistrale\Contracts\OperatorInterface;

class ArrayHelper implements OperatorInterface
{
    public function __invoke(): array
    {
        return [
            'array.get' => function(mixed $array, string $key, mixed $default = null): mixed
            {
                foreach(explode('.', $key) as $segment)
                {
                    if(!is_array($array) || !isset($array[$segment])) return $default;

                    $array = $array[$segment];
                }

                return $array;
            },
        ];
    }
}
