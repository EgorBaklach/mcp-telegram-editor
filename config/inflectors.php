<?php

use Framework\Contracts\Router\RouterInterface;
use Framework\Inflectors\{InflectorAggregate, RouteInflector};
use Magistrale\Inflectors\MixinInflector;
use Magistrale\Mixins\MacroableInterface;

return new InflectorAggregate([
    RouterInterface::class => RouteInflector::class,
    MacroableInterface::class => MixinInflector::class,
]);