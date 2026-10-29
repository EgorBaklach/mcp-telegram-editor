<?php namespace Cli\Console;

use Framework\Contracts\Console\ConsoleInterface;
use League\Container\DefinitionContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class SymfonyConsole extends Application implements ConsoleInterface
{
    private array $commands = [];

    public function __construct(private readonly DefinitionContainerInterface $container)
    {
        parent::__construct('Application CLI', '1.0.0');
    }

    public function inset(mixed $command): void
    {
        $command = $this->add($command instanceof Command ? $command : new $command); $this->commands[$command->getName()] = $command::class;
    }

    public function get(string $name): Command
    {
        if($class = $this->commands[$name] ?? null)
        {
            $command = $this->container->get($class); $command->setContainer($this->container)->construct(); $this->add($command);
        }

        return parent::get($name);
    }
}
