<?php namespace Magistrale\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleLoggerInterface
{
    public function setOutput(OutputInterface $output): void;
    public function getOutput(): OutputInterface;

    public function info(string $message): bool;
    public function comment(string $message): bool;
    public function error(string $message): bool;
}
