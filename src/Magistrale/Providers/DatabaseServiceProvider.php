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
        $this->container()->add(Capsule::class, function (): Capsule
        {
            $capsule = new Capsule;

            $capsule->addConnection($this->container()->get('database.configs'));

            // Настраиваем Capsule глобально при первом разрешении сервиса
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        });
    }
}
