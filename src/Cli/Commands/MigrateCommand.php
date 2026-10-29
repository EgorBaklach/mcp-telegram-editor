<?php namespace Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'db:migrate', description: 'Управляет миграциями базы данных (применение и откат)')]
class MigrateCommand extends AbstractCommand {}
