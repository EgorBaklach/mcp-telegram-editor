<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:manager', description: 'Управление очередью фоновых задач и планировщиком расписаний')]
class QueueManagerCommand extends AbstractCommand
{
    public function handle(string $option, array $config, mixed $payload = null): bool
    {
        return parent::handle($option, $config, $payload ?? $config);
    }
}
