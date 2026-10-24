<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use Illuminate\Database\Capsule\Manager as Capsule;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class DatabaseServiceProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    private array $database = [];

    protected array $provides = [
        Capsule::class,
    ];

    public function boot(): void
    {
        $this->database = $this->container()->get('database');
    }

    public function register(): void
    {
        $this->container()->add(Capsule::class, function(): Capsule
        {
            $capsule = new Capsule;

            $capsule->addConnection($this->database);

            // Настраиваем Capsule глобально при первом разрешении сервиса
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        });
    }
}
