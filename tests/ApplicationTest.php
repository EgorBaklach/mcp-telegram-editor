<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Framework\Contracts\Router\RouterInterface;
use Framework\Contracts\Emitter\EmitterInterface;
use League\Route\Strategy\StrategyInterface;
use Magistrale\Database\MigrationEngine;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Mcp\Server;

class ApplicationTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = require __DIR__ . '/../bootstrap/machine.php';
    }

    #[TestDox('Проверяет успешный запуск и инициализацию контейнера приложения')]
    public function testApplicationContainerBootstrap(): void
    {
        $app = Application::make($this->config);
        $this->assertInstanceOf(Application::class, $app);
    }

    #[TestDox('Проверяет наличие и корректное разрешение всех необходимых MCP-сервисов в контейнере')]
    public function testRequiredServicesAreAvailableInContainer(): void
    {
        $app = Application::make($this->config);

        $reflector = new \ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $container = $containerProperty->getValue($app);

        // Verify key MCP-related services exist
        $this->assertTrue($container->has(RouterInterface::class));
        $this->assertTrue($container->has(EmitterInterface::class));
        $this->assertTrue($container->has(ResponseFactoryInterface::class));
        $this->assertTrue($container->has(StrategyInterface::class));
        $this->assertTrue($container->has(LoggerInterface::class));
        $this->assertTrue($container->has(StreamFactoryInterface::class));
        $this->assertTrue($container->has(Server::class));
        $this->assertTrue($container->has(MigrationEngine::class));

        // Ensure resolution works correctly
        $this->assertInstanceOf(RouterInterface::class, $container->get(RouterInterface::class));
        $this->assertInstanceOf(ResponseFactoryInterface::class, $container->get(ResponseFactoryInterface::class));
        $this->assertInstanceOf(StrategyInterface::class, $container->get(StrategyInterface::class));
        $this->assertInstanceOf(LoggerInterface::class, $container->get(LoggerInterface::class));
        $this->assertInstanceOf(StreamFactoryInterface::class, $container->get(StreamFactoryInterface::class));
        $this->assertInstanceOf(Server::class, $container->get(Server::class));
        $this->assertInstanceOf(MigrationEngine::class, $container->get(MigrationEngine::class));
    }
}
