<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

final class DatabaseServiceProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    protected array $provides = [
        Capsule::class,
    ];

    public function boot(): void
    {
        // Оставляем пустым во избежание рекурсии при инициализации
    }

    public function register(): void
    {
        $this->container()->addShared(Capsule::class, function (): Capsule {
            $capsule = new Capsule;

            $capsule->addConnection([
                'driver'    => 'pgsql',
                'host'      => getenv('DB_HOST') ?: 'db',
                'port'      => getenv('DB_PORT') ?: '5432',
                'database'  => getenv('DB_DATABASE') ?: 'mcp_editor',
                'username'  => getenv('DB_USERNAME') ?: 'postgres',
                'password'  => getenv('DB_PASSWORD') ?: 'pg_pass_editor_secret_99',
                'charset'   => 'utf8',
                'prefix'    => '',
                'schema'    => 'public',
                'sslmode'   => 'prefer',
            ]);

            // Настраиваем Capsule глобально при первом разрешении сервиса
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        });
    }
}
