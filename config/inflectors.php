<?php

use Framework\Contracts\Router\RouterInterface;
use Framework\Inflectors\{AggregateInflector, RouteInflector};
use Magistrale\Contracts\{MacroableInterface, OperableInterface};
use Magistrale\Dispatchers\Source\AbstractDispatcher;
use Magistrale\Inflectors\{CallbackInflector, MixinInflector, SourceInflector};

return new AggregateInflector([
    RouterInterface::class => RouteInflector::class,
    MacroableInterface::class => MixinInflector::class,
    AbstractDispatcher::class => SourceInflector::class,
    OperableInterface::class => CallbackInflector::class,
]);
