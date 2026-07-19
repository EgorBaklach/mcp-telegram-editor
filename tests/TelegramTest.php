<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\HTTPClients\Telegram;
use Magistrale\Dispatchers\Telegram\PublishDispatcher;
use Magistrale\Dispatchers\Telegram\DeleteDispatcher;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use RuntimeException;

class TelegramTest extends TestCase
{
    private Capsule $capsule;
    private $container;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../bootstrap/machine.php';
        $app = Application::make($config);

        $reflector = new ReflectionClass($app);
        $containerProperty = $reflector->getProperty('container');
        $containerProperty->setAccessible(true);
        $this->container = $containerProperty->getValue($app);

        $this->capsule = $this->container->get(Capsule::class);
    }

    protected function tearDown(): void
    {
        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('Проверяет успешную отправку сообщения через PublishDispatcher')]
    public function testPublishDispatcherExecutesSuccessfully(): void
    {
        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('sendMessage')
            ->with($this->equalTo('Test Message'))
            ->willReturn(new Response(200));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(PublishDispatcher::class);
        $this->assertTrue($dispatcher->dispatch('Test Message'));
    }

    #[TestDox('Проверяет обработку ошибок при отправке сообщения в PublishDispatcher')]
    public function testPublishDispatcherHandlesException(): void
    {
        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('sendMessage')
            ->willThrowException(new RuntimeException('API Error'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(PublishDispatcher::class);
        $this->assertFalse($dispatcher->dispatch('Test Message'));
    }

    #[TestDox('Проверяет успешное удаление сообщения через DeleteDispatcher')]
    public function testDeleteDispatcherExecutesSuccessfully(): void
    {
        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('deleteMessage')
            ->with($this->equalTo(12345))
            ->willReturn(new Response(200));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(DeleteDispatcher::class);
        $this->assertTrue($dispatcher->dispatch(12345));
    }

    #[TestDox('Проверяет обработку ошибок при удалении сообщения в DeleteDispatcher')]
    public function testDeleteDispatcherHandlesException(): void
    {
        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('deleteMessage')
            ->willThrowException(new RuntimeException('API Error'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(DeleteDispatcher::class);
        $this->assertFalse($dispatcher->dispatch(12345));
    }
}
