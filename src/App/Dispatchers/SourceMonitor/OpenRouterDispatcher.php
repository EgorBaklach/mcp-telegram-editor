<?php namespace App\Dispatchers\SourceMonitor;

class OpenRouterDispatcher extends AbstractDispatcher
{
    protected const source = 'openrouter';

    protected const lock = 'lock:source:openrouter';
}
