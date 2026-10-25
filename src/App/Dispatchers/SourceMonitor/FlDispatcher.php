<?php namespace App\Dispatchers\SourceMonitor;

class FlDispatcher extends AbstractDispatcher
{
    protected const source = 'fl';

    protected const lock = 'lock:source:fl';
}
