<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use Illuminate\Database\Capsule\Manager as Capsule;

final class DatabaseServiceProvider extends ProviderAbstract
{
    protected array $provides = [
        Capsule::class,
    ];

    public function register(): void
    {
        $this->container()->addShared(Capsule::class, function (): Capsule
        {
            $capsule = new Capsule;

            $capsule->addConnection([
                'driver'    => 'pgsql',
                'host'      => getenv('DB_HOST'),
                'port'      => getenv('DB_PORT'),
                'database'  => getenv('DB_DATABASE'),
                'username'  => getenv('DB_USERNAME'),
                'password'  => getenv('DB_PASSWORD'),
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
