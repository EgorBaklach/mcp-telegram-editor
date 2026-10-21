<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use Illuminate\Support\Traits\Macroable;
use League\Container\DefinitionContainerInterface;
use Magistrale\Logging\ConsoleLoggerInterface;
use Magistrale\Mixins\MacroableInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command implements CommandInterface, MacroableInterface
{
    use Macroable;

    public DefinitionContainerInterface $container;

    public ConsoleLoggerInterface $logger;

    public InputInterface $input;

    public function setContainer(DefinitionContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function construct(): void
    {
        $this->logger = $this->container->get(ConsoleLoggerInterface::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input; $this->logger->setOutput($output); $dispatchers = $this->container->get('commands')[static::class] ?? [];

        foreach($dispatchers as $option => $class)
        {
            if($this->hasOption($option))
            {
                $this->container->get($class)->build($this)->dispatch($this->getOption($option)); return Command::SUCCESS;
            }
        }

        $this->logger->error('Не указана опция! Добавьте флаг: --'.implode(', --', array_keys($dispatchers)));

        $this->logger->info('Используйте --help для просмотра всех доступных опций.');

        return Command::INVALID;
    }
}
