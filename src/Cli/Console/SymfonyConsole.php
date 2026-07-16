<?php namespace Cli\Console;

use Framework\Contracts\Console\CommandInterface;
use Framework\Contracts\Console\ConsoleInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class SymfonyConsole extends Application implements ConsoleInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
        parent::__construct('Application CLI', '1.0.0');
    }

    public function add(Command $command): ?Command
    {
        $command = parent::add($command); if ($command instanceof CommandInterface) $command->setContainer($this->container); return $command;
    }

    public function find(string $name): Command
    {
        $command = parent::find($name); if ($command instanceof CommandInterface) $command->construct(); return $command;
    }
}
