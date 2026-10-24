<?php

use Magistrale\Clients\{FlClient, OpenRouterClient};
use Magistrale\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};

return [
    OpenRouterDispatcher::class => OpenRouterClient::class,
    FlDispatcher::class => FlClient::class,
];
