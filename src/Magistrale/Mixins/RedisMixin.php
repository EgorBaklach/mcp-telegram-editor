<?php namespace Magistrale\Mixins;

use Closure;

class RedisMixin
{
    public function encodeResp(): Closure
    {
        return function(array $args): string
        {
            $payload = '*'.count($args)."\r\n"; foreach($args as $arg) $payload .= '$'.strlen((string) $arg)."\r\n{$arg}\r\n"; return $payload;
        };
    }
}
