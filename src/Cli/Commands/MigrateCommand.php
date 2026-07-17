<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use League\Container\DefinitionContainerInterface;
use Magistrale\Dispatchers\Migration\AbstractMigrationDispatcher;
use Magistrale\Dispatchers\Migration\CreateMigrationDispatcher;
use Magistrale\Dispatchers\Migration\DownMigrationDispatcher;
use Magistrale\Dispatchers\Migration\UpMigrationDispatcher;
use Magistrale\Logging\MigrationLoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:migrate', description: 'Управляет миграциями базы данных (применение и откат)')]
class MigrateCommand extends Command implements CommandInterface
{
    public DefinitionContainerInterface $container;
    public MigrationLoggerInterface $logger;

    private const dispatchers = [
        'up' => UpMigrationDispatcher::class,
        'down' => DownMigrationDispatcher::class,
        'new' => CreateMigrationDispatcher::class,
    ];

    public function setContainer(DefinitionContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function construct(): void
    {
        $this->logger = $this->container->get(MigrationLoggerInterface::class);
    }

    protected function configure(): void
    {
        $this->addOption('up', null, InputOption::VALUE_NONE, 'Применить все ожидающие миграции (накатить)');
        $this->addOption('down', null, InputOption::VALUE_OPTIONAL, 'Откат миграций (последний батч, "all" для всех, либо ID конкретной миграции)', false);
        $this->addOption('new', null, InputOption::VALUE_OPTIONAL, 'Создать новый файл миграции с указанным именем', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->setOutput($output);

        foreach(self::dispatchers as $option => $class)
        {
            if($value = $input->getOption($option))
            {
                $this->container->get($class)->build($this)->dispatch($value === true ? null : $value); return Command::SUCCESS;
            }
        }

        $this->logger->error('Не указано действие! Добавьте флаг --up, --down или --new');
        $this->logger->info('Используйте --help для просмотра всех доступных опций.');

        return Command::INVALID;
    }
}
