<?php

use Magistrale\Clients\{Fl, OpenRouter};
use Magistrale\Dispatchers\Source\{FlDispatcher, OpenRouterDispatcher};

return [
    OpenRouterDispatcher::class => OpenRouter::class,
    FlDispatcher::class => Fl::class,
];
