<?php

use App\Clients\{FlClient, OpenRouterClient};
use App\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};

return [
    OpenRouterDispatcher::class => OpenRouterClient::class,
    FlDispatcher::class => FlClient::class,
];
