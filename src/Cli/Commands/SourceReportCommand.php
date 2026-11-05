<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'source:report', description: 'Формирует и отправляет сводный отчёт по источникам в Telegram')]
class SourceReportCommand extends AbstractCommand
{
    public function handle(string $option, array $config, mixed $payload = null): bool
    {
        return parent::handle($option, $config, $this->callback->array('get', $this->commands, SourceMonitorCommand::class));
    }
}