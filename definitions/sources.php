<?php

use App\Dispatchers\Source\{FLDispatcher, HHDispatcher, OpenRouterDispatcher};
use Magistrale\Clients\{FLClient, HHClient, OpenRouterClient};

return [
    OpenRouterDispatcher::class => OpenRouterClient::class,
    FLDispatcher::class => FLClient::class,
    HHDispatcher::class => HHClient::class,
];
