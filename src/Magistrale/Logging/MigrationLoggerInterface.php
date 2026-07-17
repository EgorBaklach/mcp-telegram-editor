<?php namespace Magistrale\Logging;

use Symfony\Component\Console\Output\OutputInterface;

interface MigrationLoggerInterface
{
    public function setOutput(OutputInterface $output): void;

    public function info(string $message): void;
    public function comment(string $message): void;
    public function error(string $message): void;
}
