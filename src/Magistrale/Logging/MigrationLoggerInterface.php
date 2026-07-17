<?php namespace Magistrale\Logging;

interface MigrationLoggerInterface
{
    public function info(string $message): void;
    public function comment(string $message): void;
    public function error(string $message): void;
}
