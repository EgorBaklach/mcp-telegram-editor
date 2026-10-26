<?php

use App\Dispatchers\Source\AbstractDispatcher;
use App\Inflectors\SourceInflector;
use Framework\Contracts\Router\RouterInterface;
use Framework\Inflectors\{AggregateInflector, RouteInflector};
use Magistrale\Contracts\{MacroableInterface, ObservableInterface, OperableInterface};
use Magistrale\Inflectors\{CallbackInflector, MixinInflector, ObserverInflector};

return new AggregateInflector([
    RouterInterface::class => RouteInflector::class,
    MacroableInterface::class => MixinInflector::class,
    AbstractDispatcher::class => SourceInflector::class,
    OperableInterface::class => CallbackInflector::class,
    ObservableInterface::class => ObserverInflector::class,
]);
