<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\Telegram;
use Magistrale\Dispatchers\Telegram\{PublishDispatcher, DeleteDispatcher, DeleteByTextDispatcher, EditDispatcher, SearchPostsDispatcher};
use Magistrale\Dispatchers\Telegram\ResultInterface;
use Magistrale\Dispatchers\Migration\UpDispatcher;
use App\Models\TelegramPost;
use App\Tools\SearchPostsTool;
use Cli\Commands\MigrateCommand;
use Symfony\Component\Console\Output\NullOutput;
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

        $engine = $this->container->get(UpDispatcher::class);
        $command = new MigrateCommand();
        $command->setContainer($this->container);
        $command->construct();
        $command->logger->setOutput(new NullOutput());
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

    #[TestDox('Проверяет успешное редактирование поста через EditDispatcher')]
    public function testEditDispatcherExecutesSuccessfully(): void
    {
        TelegramPost::truncate();
        TelegramPost::create(['message_id' => 777, 'text' => 'Old content']);

        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('editMessage')
            ->with($this->equalTo(['message_id' => 777, 'text' => 'New content']))
            ->willReturn(new Response(200, [], '{"ok":true,"result":{"message_id":777,"text":"New content"}}'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(EditDispatcher::class);
        $this->assertTrue($dispatcher->dispatch(['message_id' => 777, 'text' => 'New content']));

        $post = TelegramPost::where('message_id', 777)->first();
        $this->assertNotNull($post);
        $this->assertEquals('New content', $post->text);

        TelegramPost::truncate();
    }

    #[TestDox('Проверяет обработку ошибок при редактировании в EditDispatcher')]
    public function testEditDispatcherHandlesException(): void
    {
        $mockClient = $this->createMock(Telegram::class);
        $mockClient->expects($this->once())
            ->method('editMessage')
            ->willThrowException(new RuntimeException('API Error'));

        $this->container->add(Telegram::class, $mockClient);

        $dispatcher = $this->container->get(EditDispatcher::class);
        $this->assertFalse($dispatcher->dispatch(['message_id' => 777, 'text' => 'New content']));
    }

    #[TestDox('Проверяет успешный поиск постов по подстроке через SearchPostsTool')]
    public function testSearchPostsToolExecutesSuccessfully(): void
    {
        TelegramPost::truncate();
        TelegramPost::create(['message_id' => 111, 'text' => 'First search target']);
        TelegramPost::create(['message_id' => 222, 'text' => 'Second search query']);
        TelegramPost::create(['message_id' => 333, 'text' => 'Unrelated content']);

        $tool = $this->container->get(SearchPostsTool::class);
        $result = $tool->search('search');

        $data = json_decode($result, true);
        $this->assertCount(2, $data);

        $ids = array_column($data, 'message_id');
        $this->assertContains(111, $ids);
        $this->assertContains(222, $ids);
        $this->assertNotContains(333, $ids);

        TelegramPost::truncate();
    }

    #[TestDox('Проверяет генерацию исключения в SearchPostsTool при пустом запросе')]
    public function testSearchPostsToolThrowsExceptionOnEmptyQuery(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $tool = $this->container->get(SearchPostsTool::class);
        $tool->search('');
    }

    #[TestDox('Проверяет успешное выполнение SearchPostsDispatcher и реализацию ResultInterface')]
    public function testSearchPostsDispatcherExecutesSuccessfully(): void
    {
        TelegramPost::truncate();
        TelegramPost::create(['message_id' => 444, 'text' => 'Searchable dispatcher text']);

        $dispatcher = $this->container->get(SearchPostsDispatcher::class);
        $this->assertInstanceOf(ResultInterface::class, $dispatcher);
        $this->assertTrue($dispatcher->dispatch('dispatcher'));

        $results = $dispatcher->getResults();
        $this->assertCount(1, $results);
        $this->assertEquals(444, $results[0]['message_id']);
        $this->assertEquals('Searchable dispatcher text', $results[0]['text']);

        TelegramPost::truncate();
    }
}
