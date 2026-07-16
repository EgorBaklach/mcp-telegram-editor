<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hello:world', description: 'Outputs "Hello World"')]
class HelloWorldCommand extends Command implements CommandInterface
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello World'); return Command::SUCCESS;
    }

    public function setContainer(ContainerInterface $container): void
    {

    }

    public function construct(): void
    {

    }
}
