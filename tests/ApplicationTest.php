<?php namespace Tests;

use Cli\Commands\{MigrateCommand, SourceMonitorCommand};
use Framework\Contracts\Console\ConsoleInterface;
use Framework\Contracts\Router\RouterInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use League\Container\DefinitionContainerInterface;
use League\Container\Exception\ContainerException;
use Mcp\Server;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface};
use Psr\Log\LoggerInterface;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->bootContainer();
    }

    #[TestDox('Контейнер загружает все модульные определения из definitions/*.php и разрешает ключевые сервисы')]
    public function testContainerResolvesCoreServicesAndDefinitions(): void
    {
        foreach(['agents', 'clients', 'commands', 'database', 'dependencies', 'mcp', 'mixins', 'operators', 'redis', 'sources'] as $key)
        {
            $this->assertTrue($this->container->has($key), "Определение {$key} должно быть загружено из definitions/{$key}.php");
            $this->assertIsArray($this->container->get($key));
        }

        $this->assertSame($this->container, $this->container->get(DefinitionContainerInterface::class));
        $this->assertInstanceOf(LoggerInterface::class, $this->container->get(LoggerInterface::class));
        $this->assertInstanceOf(StreamFactoryInterface::class, $this->container->get(StreamFactoryInterface::class));
        $this->assertInstanceOf(ResponseFactoryInterface::class, $this->container->get(ResponseFactoryInterface::class));
        $this->assertInstanceOf(RouterInterface::class, $this->container->get(RouterInterface::class));
        $this->assertInstanceOf(ConsoleInterface::class, $this->container->get(ConsoleInterface::class));
        $this->assertInstanceOf(Capsule::class, $this->container->get(Capsule::class));
        $this->assertInstanceOf(Server::class, $this->container->get(Server::class));
    }

    #[TestDox('MixinInflector автоматически подключает макросы из definitions/mixins.php к классам MacroableInterface')]
    public function testMixinInflectorRegistersMacrosOnResolvedObjects(): void
    {
        $migrate = $this->container->get(MigrateCommand::class);
        $monitor = $this->container->get(SourceMonitorCommand::class);

        $this->assertTrue($migrate::hasMacro('hasOption'));
        $this->assertTrue($migrate::hasMacro('getOption'));
        $this->assertTrue($monitor::hasMacro('hasOption'));
    }

    #[TestDox('AggregateProvider выбрасывает ContainerException при попытке регистрации неизвестного сервиса')]
    public function testProviderAggregateThrowsOnUnknownService(): void
    {
        $providers = require __DIR__.'/../config/providers.php';

        $this->expectException(ContainerException::class);
        $providers->register('NonExistentService');
    }
}
