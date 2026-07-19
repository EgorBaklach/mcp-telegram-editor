<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\Telegram;
use Magistrale\Dispatchers\Telegram\PublishDispatcher;
use Magistrale\Dispatchers\Telegram\DeleteDispatcher;
use Magistrale\Dispatchers\Telegram\DeleteByTextDispatcher;
use App\Models\TelegramPost;
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

        $engine = $this->container->get(\Magistrale\Dispatchers\Migration\UpDispatcher::class);
        $command = new \Cli\Commands\MigrateCommand();
        $command->setContainer($this->container);
        $command->construct();
        $command->logger->setOutput(new \Symfony\Component\Console\Output\NullOutput());
        $engine->build($command);
        $engine->dispatch();
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
            ->willReturn(new Response(200, [], '{"ok":true,"result":{"message_id":123,"text":"Test Message"}}'));

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
            ->willReturn(new Response(200, [], '{"ok":true,"result":true}'));

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

    #[TestDox('Проверяет, что PublishDispatcher сохраняет опубликованное сообщение в БД')]
    public function testPublishDispatcherSavesToDb(): void
    {
        TelegramPost::truncate();

        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('sendMessage')
            ->with($this->equalTo('Hello DB Test'))
            ->willReturn(new Response(200, [], '{"ok":true,"result":{"message_id":999,"text":"Hello DB Test"}}'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(PublishDispatcher::class);
        $this->assertTrue($dispatcher->dispatch('Hello DB Test'));

        $post = TelegramPost::where('message_id', 999)->first();
        $this->assertNotNull($post);
        $this->assertEquals('Hello DB Test', $post->text);
        
        TelegramPost::truncate();
    }

    #[TestDox('Проверяет успешное удаление сообщения по тексту через DeleteByTextDispatcher')]
    public function testDeleteByTextDispatcherExecutesSuccessfully(): void
    {
        TelegramPost::truncate();
        TelegramPost::create(['message_id' => 888, 'text' => 'Delete me by text']);

        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('deleteMessage')
            ->with($this->equalTo(888))
            ->willReturn(new Response(200, [], '{"ok":true,"result":true}'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(DeleteByTextDispatcher::class);
        $this->assertTrue($dispatcher->dispatch('me by'));

        $this->assertNull(TelegramPost::where('message_id', 888)->first());
        
        TelegramPost::truncate();
    }

    #[TestDox('Проверяет возвращение false в DeleteByTextDispatcher, если текст не найден в БД')]
    public function testDeleteByTextDispatcherReturnsFalseIfNotFound(): void
    {
        TelegramPost::truncate();

        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->never())->method('deleteMessage');

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(DeleteByTextDispatcher::class);
        $this->assertFalse($dispatcher->dispatch('Nonexistent text'));
    }
}
