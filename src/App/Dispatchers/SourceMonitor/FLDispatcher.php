<?php namespace App\Dispatchers\SourceMonitor;

class FLDispatcher extends AbstractDispatcher
{
    protected const source = 'fl';

    protected const lock = 'lock:source:fl';
}
