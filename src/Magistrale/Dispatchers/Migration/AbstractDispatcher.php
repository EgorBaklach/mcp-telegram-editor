<?php namespace Magistrale\Dispatchers\Migration;

use Cli\Commands\MigrateCommand;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\Logging\MigrationLoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected MigrateCommand $command;

    public function __construct(protected readonly Capsule $capsule) {}

    public function build(MigrateCommand $command): self
    {
        $this->command = $command;

        if(!$this->capsule::schema()->hasTable('migrations'))
        {
            $this->command->logger->comment('Создается таблица migrations...');

            $this->capsule::schema()->create('migrations', function (Blueprint $table)
            {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });

            $this->command->logger->info('Таблица migrations успешно создана.');
        }

        return $this;
    }

    abstract public function dispatch(mixed $payload = null): bool;
}
