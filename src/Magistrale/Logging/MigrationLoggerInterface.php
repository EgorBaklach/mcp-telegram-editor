<?php namespace Magistrale\Logging;

use Symfony\Component\Console\Output\OutputInterface;

interface MigrationLoggerInterface
{
    public function setOutput(OutputInterface $output): void;

    public function info(string $message): bool;
    public function comment(string $message): bool;
    public function error(string $message): bool;
}
