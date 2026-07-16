<?php namespace Framework\Contracts\Emitter;

use Framework\Contracts\Router\RouterInterface;

interface EmitterInterface
{
    public function emit(RouterInterface $router): bool;
}