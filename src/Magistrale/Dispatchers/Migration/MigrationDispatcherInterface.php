<?php namespace Magistrale\Dispatchers\Migration;

use Cli\Commands\MigrateCommand;

interface MigrationDispatcherInterface
{
    /**
     * Инициализирует диспетчер и подготавливает необходимые структуры (например, таблицу миграций).
     *
     * @param MigrateCommand $command
     * @return self
     */
    public function build(MigrateCommand $command): self;

    /**
     * Запускает выполнение логики диспетчера.
     *
     * @param mixed $payload
     * @return bool
     */
    public function dispatch(mixed $payload = null): bool;
}
