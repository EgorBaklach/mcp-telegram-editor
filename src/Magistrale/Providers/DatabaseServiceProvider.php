<?php namespace Magistrale\Providers;

use Framework\Providers\AbstractProvider;
use Illuminate\Database\Capsule\Manager as Capsule;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class DatabaseServiceProvider extends AbstractProvider implements BootableServiceProviderInterface
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

            $capsule->addConnection($this->database['access']);

            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            foreach($this->database['mixins'] as $builder => $mixin)
            {
                $builder::mixin($this->container()->get($mixin));
            }

            return $capsule;
        });
    }
}
