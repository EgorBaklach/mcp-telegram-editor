<?php namespace App\Dispatchers\SourceMonitor;

class HHDispatcher extends AbstractDispatcher
{
    protected const source = 'hh';

    protected const lock = 'lock:source:hh';
}
