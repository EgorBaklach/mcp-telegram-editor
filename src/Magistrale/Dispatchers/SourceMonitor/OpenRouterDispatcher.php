<?php namespace Magistrale\Dispatchers\SourceMonitor;

class OpenRouterDispatcher extends AbstractDispatcher
{
    protected const sourceName = 'openrouter';

    protected const lockKey = 'lock:source:openrouter';
}
