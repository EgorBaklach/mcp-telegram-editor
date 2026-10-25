<?php namespace Tests;

use App\Dispatchers\Telegram\{DeleteByTextDispatcher, DeleteDispatcher, EditDispatcher, PublishDispatcher, SearchPostsDispatcher};
use App\Models\TelegramPost;
use App\Tools\Mcp\PingTool;
use App\Tools\Telegram\{DeleteByTextTool, DeleteTool, EditTool, PublishTool, SearchPostsTool};
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;
use Magistrale\Clients\TelegramClient;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;

class TelegramTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->capsule = $this->container->get(Capsule::class);
        $this->capsule->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if($this->capsule->getConnection()->transactionLevel() > 0)
        {
            $this->capsule->getConnection()->rollBack();
        }

        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('PublishDispatcher и PublishTool: публикуют пост в Telegram, декодируют message_id через TelegramMixin и сохраняют в БД')]
    public function testPublishLifecycleAndErrorHandling(): void
    {
        $mock = $this->createMock(TelegramClient::class);
        $mock->expects($this->exactly(2))
            ->method('publish')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], '{"ok":true,"result":{"message_id":900001}}'),
                new Response(500, [], 'Internal Server Error')
            );

        $this->container->add(TelegramClient::class, $mock);
        $tool = $this->container->get(PublishTool::class);

        $this->assertSame('success', $tool->publish('<b>Strong Elephant</b>'));
        $this->assertSame('<b>Strong Elephant</b>', TelegramPost::where('message_id', 900001)->first()?->text);

        $this->assertSame('failed', $tool->publish('<b>Fail case</b>'));
    }

    #[TestDox('EditDispatcher и EditTool: редактируют пост, создают или обновляют запись в БД через updateOrCreate и обрабатывают сбои')]
    public function testEditDispatcherUpdatesAndCreatesRecords(): void
    {
        $mock = $this->createMock(TelegramClient::class);
        $mock->expects($this->exactly(2))
            ->method('edit')
            ->willReturnCallback(function(array $payload): Response
            {
                if($payload['message_id'] === 999) throw new RuntimeException('Telegram network failure');

                return new Response(200, [], '{"ok":true}');
            });

        $this->container->add(TelegramClient::class, $mock);
        $tool = $this->container->get(EditTool::class);

        $this->assertSame('success', $tool->edit(900002, 'Updated text'));
        $this->assertSame('Updated text', TelegramPost::where('message_id', 900002)->first()?->text);

        $this->assertSame('failed', $tool->edit(999, 'Should fail'));
    }

    #[TestDox('DeleteDispatcher и DeleteByTextDispatcher: экранируют спецсимволы ILIKE (%, _) и удаляют пост из Telegram и локальной БД')]
    public function testDeleteAndDeleteByTextWithWildcardEscaping(): void
    {
        TelegramPost::create(['message_id' => 900010, 'text' => 'Post with 100% normal content']);
        TelegramPost::create(['message_id' => 900011, 'text' => 'Post with 100000 coins']);

        $mock = $this->createMock(TelegramClient::class);
        $mock->expects($this->once())
            ->method('remove')
            ->with(900010)
            ->willReturn(new Response(200, [], '{"ok":true}'));

        $this->container->add(TelegramClient::class, $mock);

        $deleteByText = $this->container->get(DeleteByTextDispatcher::class);
        $this->assertTrue($deleteByText->dispatch('100%'));
        $this->assertNull(TelegramPost::where('message_id', 900010)->first(), 'Запись должна удаляться из БД после удаления в Telegram');
        $this->assertNotNull(TelegramPost::where('message_id', 900011)->first(), 'Спецсимвол % должен быть экранирован и не задевать 100000');

        $this->assertFalse($deleteByText->dispatch('non_existent_substring_xyz'));
    }

    #[TestDox('SearchPostsDispatcher и SearchPostsTool: находят посты по экранированной подстроке и возвращают валидный JSON')]
    public function testSearchPostsWithEscaping(): void
    {
        TelegramPost::create(['message_id' => 900020, 'text' => 'Target_underscore_match']);
        TelegramPost::create(['message_id' => 900021, 'text' => 'TargetXunderscoreXmatch']);

        $tool = $this->container->get(SearchPostsTool::class);
        $decoded = json_decode($tool->search('Target_underscore'), true);

        $this->assertCount(1, $decoded);
        $this->assertSame(900020, $decoded[0]['message_id']);
    }

    #[TestDox('Все Telegram MCP-инструменты выбрасывают InvalidArgumentException при пустых аргументах, а PingTool возвращает pong')]
    public function testToolsInputValidationAndPing(): void
    {
        $this->assertSame('pong: test', $this->container->get(PingTool::class)->ping('test'));

        foreach([
            fn() => $this->container->get(PublishTool::class)->publish(''),
            fn() => $this->container->get(DeleteTool::class)->delete(0),
            fn() => $this->container->get(DeleteByTextTool::class)->delete(''),
            fn() => $this->container->get(EditTool::class)->edit(0, 'text'),
            fn() => $this->container->get(EditTool::class)->edit(1, ''),
            fn() => $this->container->get(SearchPostsTool::class)->search(''),
        ] as $call)
        {
            try
            {
                $call(); $this->fail('Ожидалось исключение InvalidArgumentException');
            }
            catch(InvalidArgumentException $e)
            {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }
}
