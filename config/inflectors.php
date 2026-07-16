<?php

use Framework\Contracts\Router\RouterInterface;
use Framework\Inflectors\{InflectorAggregate, RouteInflector};

return new InflectorAggregate([RouterInterface::class => RouteInflector::class]);