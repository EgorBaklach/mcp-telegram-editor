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
        $level = strtoupper($level); fwrite($this->handle, sprintf('[%s] %s %s%s'.PHP_EOL, date('H:i:s'), $level, $message, json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    }
}
