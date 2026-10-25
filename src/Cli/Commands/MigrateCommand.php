<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:migrate', description: 'Управляет миграциями базы данных (применение и откат)')]
class MigrateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('up', null, InputOption::VALUE_NONE, 'Применить все ожидающие миграции (накатить)');
        $this->addOption('down', null, InputOption::VALUE_OPTIONAL, 'Откат миграций (последний батч, all для всех, либо ID конкретной миграции)', false);
        $this->addOption('new', null, InputOption::VALUE_OPTIONAL, 'Создать новый файл миграции с указанным именем', false);
    }
}
