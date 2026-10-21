<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'source:monitor', description: 'Запускает мониторинг внешних источников данных через AI-агента (source-monitor)')]
class SourceMonitorCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->addOption('openrouter', null, InputOption::VALUE_OPTIONAL, 'Запустить мониторинг бесплатных AI-моделей OpenRouter через указанного агента (например, --openrouter=agy)', false);
    }
}
