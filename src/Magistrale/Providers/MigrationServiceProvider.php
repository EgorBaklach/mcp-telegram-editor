<?php namespace Magistrale\Providers;

use Framework\Contracts\Template\TemplateInterface;
use Framework\Providers\ProviderAbstract;
use Magistrale\Logging\ConsoleMigrationLogger;
use Magistrale\Logging\MigrationLoggerInterface;

class MigrationServiceProvider extends ProviderAbstract
{
    protected array $provides = [
        MigrationLoggerInterface::class
    ];

    public function register(): void
    {
        $this->container()->add(MigrationLoggerInterface::class, ConsoleMigrationLogger::class);
    }
}