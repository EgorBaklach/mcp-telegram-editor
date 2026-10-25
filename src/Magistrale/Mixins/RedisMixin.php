<?php namespace Magistrale\Mixins;

use Closure;

class RedisMixin
{
    public function encode(): Closure
    {
        return function(array $args): string
        {
            $eol = chr(13).PHP_EOL; $payload = '*'.count($args).$eol;

            foreach($args as $arg)
            {
                $item = (string) $arg; $payload .= '$'.strlen($item).$eol.$item.$eol;
            }

            return $payload;
        };
    }
}
