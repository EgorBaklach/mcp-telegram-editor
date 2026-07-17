<?php namespace Magistrale\Logging;

use Symfony\Component\Console\Output\OutputInterface;

class ConsoleMigrationLogger implements MigrationLoggerInterface
{
    private readonly OutputInterface $output;

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function info(string $message): void
    {
        $this->output->writeln("<info>{$message}</info>");
    }

    public function comment(string $message): void
    {
        $this->output->writeln("<comment>{$message}</comment>");
    }

    public function error(string $message): void
    {
        $this->output->writeln("<error>{$message}</error>");
    }
}
