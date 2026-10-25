<?php namespace Magistrale\Dispatchers\Migration;

use Cli\Commands\MigrateCommand;

class CreateDispatcher extends AbstractDispatcher
{
    public function build(MigrateCommand $command): self
    {
        $this->command = $command; return $this;
    }

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return $this->command->logger->error('Укажите название миграции через --new=ИМЯ_МИГРАЦИИ');

        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $payload); $name = date('Y_m_d_His').'_'.preg_replace('/[^a-z0-9_]/', '_', strtolower($snake)).'.php';

        $this->fs->dumpFile('database/migrations/'.$name, $this->fs->readFile('storage/app/templates/migration.php'));

        return $this->command->logger->info('Миграция успешно создана: database/migrations/'.$name);
    }
}
