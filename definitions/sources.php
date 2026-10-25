<?php

use App\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};
use Magistrale\Clients\{FlClient, OpenRouterClient};

return [
    OpenRouterDispatcher::class => OpenRouterClient::class,
    FlDispatcher::class => FlClient::class,
];
