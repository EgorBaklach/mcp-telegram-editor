<?php namespace Magistrale\Logging;

use Psr\Log\AbstractLogger;

final class StderrLogger extends AbstractLogger
{
    /** @var resource */
    private $handle;

    public function __construct(string $stream = 'php://stderr')
    {
        $this->handle = fopen($stream, 'w');
    }

    public function log($level, $message, array $context = []): void
    {
        $ts  = date('H:i:s');
        $lvl = strtoupper(is_string($level) ? $level : (string) $level);
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        fwrite($this->handle, "[{$ts}] {$lvl} {$message}{$ctx}\n");
    }
}
