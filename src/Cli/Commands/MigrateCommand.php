<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use Magistrale\Database\MigrationEngine;
use Magistrale\Logging\ConsoleMigrationLogger;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:migrate', description: 'Управляет миграциями базы данных (применение и откат)')]
class MigrateCommand extends Command implements CommandInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function construct(): void
    {
    }

    protected function configure(): void
    {
        $this->addOption(
            'down',
            null,
            InputOption::VALUE_OPTIONAL,
            'Откат миграций (последний батч, "all" для всех, либо ID конкретной миграции)',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $engine = $this->container->get(MigrationEngine::class);
        $logger = new ConsoleMigrationLogger($output);
        $downOption = $input->getOption('down');

        if ($downOption === false) {
            $success = $engine->up($logger);
        } else {
            $success = $engine->down($downOption, $logger);
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
