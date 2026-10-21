<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use League\Container\DefinitionContainerInterface;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\Logging\ConsoleLoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command implements CommandInterface
{
    public DefinitionContainerInterface $container;

    public ConsoleLoggerInterface $logger;

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
        $this->logger->setOutput($output); $dispatchers = $this->container->get('commands')[static::class] ?? [];

        foreach($dispatchers as $option => $class)
        {
            $value = $input->getOption($option); if($value !== false)
            {
                /** @var DispatcherInterface $dispatcher */ $dispatcher = $this->container->get($class)->build($this);

                $dispatcher->dispatch($value === true ? null : $value);

                return Command::SUCCESS;
            }
        }

        $this->logger->error('Не указана опция! Добавьте флаг: --'.implode(', --', array_keys($dispatchers)));

        $this->logger->info('Используйте --help для просмотра всех доступных опций.');

        return Command::INVALID;
    }
}
