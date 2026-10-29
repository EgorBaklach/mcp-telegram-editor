<?php namespace Magistrale\Dispatchers\Migration;

class CreateDispatcher extends AbstractDispatcher
{
    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return $this->logger->error('Укажите название миграции через --new=ИМЯ_МИГРАЦИИ');

        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $payload); $name = date('Y_m_d_His').'_'.preg_replace('/[^a-z0-9_]/', '_', strtolower($snake)).'.php';

        $this->fs->dumpFile('database/migrations/'.$name, $this->fs->readFile('storage/app/templates/migration.php'));

        return $this->logger->info('Миграция успешно создана: database/migrations/'.$name);
    }
}
