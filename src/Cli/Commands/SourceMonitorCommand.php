<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'source:monitor', description: 'Запускает мониторинг внешних источников данных через AI-агента (source-monitor)')]
class SourceMonitorCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('openrouter', null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг бесплатных AI-моделей OpenRouter через указанного агента (например, --openrouter=agy)', false);
        $this->addOption('fl', null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг новых заказов FL.ru через указанного агента (например, --fl=agy)', false);
        $this->addOption('hh', null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг новых вакансий HH.ru через указанного агента (например, --hh=agy)', false);
    }
}
