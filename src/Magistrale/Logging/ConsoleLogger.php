<?php namespace Magistrale\Logging;

use Magistrale\Contracts\ConsoleLoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger implements ConsoleLoggerInterface
{
    private readonly OutputInterface $output;

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function info(string $message): true
    {
        $this->output->writeln('<info>'.$message.'</info>'); return true;
    }

    public function comment(string $message): true
    {
        $this->output->writeln('<comment>'.$message.'</comment>'); return true;
    }

    public function error(string $message): false
    {
        $this->output->writeln('<error>'.$message.'</error>'); return false;
    }
}
