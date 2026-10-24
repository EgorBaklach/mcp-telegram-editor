<?php namespace Magistrale\Contracts;

interface MacroableInterface
{
    public static function mixin(object $mixin, bool $replace = true): void;

    public static function hasMacro(string $name): bool;
}
