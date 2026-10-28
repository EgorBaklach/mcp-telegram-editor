<?php namespace Magistrale\Contracts;

interface MacroableInterface
{
    public static function mixin(MixinInterface $mixin, bool $replace = true): void;

    public static function hasMacro(string $name): bool;
}
