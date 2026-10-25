<?php namespace Tests;

use App\Dispatchers\Source\OpenRouterDispatcher;
use App\Models\OpenRouterModel;
use App\Tools\OpenRouter\{CheckNewModelsTool, MarkPublishedTool};
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;
use League\Container\DefinitionContainerInterface;
use Magistrale\Clients\OpenRouterClient;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class OpenRouterTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->capsule = $this->container->get(Capsule::class);
        $this->capsule->getConnection()->beginTransaction();
        OpenRouterModel::query()->delete();
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

    #[TestDox('SyncDispatcher и OpenRouterMixin: фильтруют платные модели, сохраняют бесплатные и корректно обновляют released_at без дубликатов даже при NULL в БД')]
    public function testSyncDispatcherFiltersPaidDeduplicatesNullReleasedAtAndBackfills(): void
    {
        // Предварительно создаём существующую модель с released_at = NULL (уязвимый кейс для isset vs array_key_exists)
        OpenRouterModel::create([
            'model_id' => 'vendor/existing-null:free',
            'name' => 'Existing Null ReleasedAt',
            'accessible' => 'free',
            'released_at' => null,
        ]);

        $payload = json_encode([
            'data' => [
                [
                    'id' => 'vendor/existing-null:free',
                    'name' => 'Existing Null ReleasedAt',
                    'created' => 1753164000,
                    'pricing' => ['prompt' => '0', 'completion' => '0'],
                ],
                [
                    'id' => 'vendor/new-free:free',
                    'name' => 'New Free Model',
                    'created' => 1753164000,
                    'context_length' => 131072,
                    'architecture' => ['modality' => 'text->text'],
                    'description' => 'Fast free model',
                    'reasoning' => ['enabled' => true],
                    'supported_parameters' => ['tools', 'temperature'],
                    'pricing' => ['prompt' => '0', 'completion' => '0'],
                ],
                [
                    'id' => 'vendor/paid-model',
                    'name' => 'Paid Model',
                    'pricing' => ['prompt' => '0.001', 'completion' => '0.002'],
                ],
            ],
        ]);

        $mock = $this->createMock(OpenRouterClient::class);
        $mock->method('models')->willReturn(new Response(200, [], $payload));
        $this->container->add(OpenRouterClient::class, $mock);

        $dispatcher = $this->container->get(OpenRouterDispatcher::class);
        $this->assertTrue($dispatcher->dispatch());

        $results = $dispatcher->getResults();
        $this->assertCount(1, $results, 'В результаты должна попасть только действительно новая бесплатная модель');
        $this->assertSame('vendor/new-free:free', $results[0]['model_id']);
        $this->assertTrue($results[0]['has_reasoning']);
        $this->assertSame(['tools', 'temperature'], $results[0]['supported_parameters']);

        // Проверяем, что для новой модели сохранился released_at, а существующая не продублировалась
        $created = OpenRouterModel::where('model_id', 'vendor/new-free:free')->first();
        $this->assertNotNull($created?->released_at, 'Поле released_at должно быть заполнено у новой модели');

        // Повторная синхронизация того же ответа должна вернуть 0 новых моделей
        $this->assertTrue($dispatcher->dispatch());
        $this->assertCount(0, $dispatcher->getResults());
    }

    #[TestDox('CheckNewModelsTool и MarkPublishedTool: обрабатывают успешный синк, ошибки HTTP/исключения и пометку модели как опубликованной')]
    public function testCheckNewModelsAndMarkPublishedTools(): void
    {
        $mock = $this->createMock(OpenRouterClient::class);
        $mock->expects($this->exactly(2))
            ->method('models')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], json_encode([
                    'data' => [[
                        'id' => 'vendor/tool-model:free',
                        'name' => 'Tool Model',
                        'context_length' => 131072,
                        'architecture' => ['modality' => 'text->text'],
                        'description' => 'Tool model description',
                        'supported_parameters' => ['tools'],
                        'pricing' => ['prompt' => '0', 'completion' => '0'],
                    ]],
                ])),
                null
            );

        $this->container->add(OpenRouterClient::class, $mock);

        $checkTool = $this->container->get(CheckNewModelsTool::class);
        $okJson = json_decode($checkTool->checkNewModels(), true);
        $this->assertSame('ok', $okJson['status']);
        $this->assertCount(1, $okJson['new_models']);

        $errJson = json_decode($checkTool->checkNewModels(), true);
        $this->assertSame('error', $errJson['status']);

        $markTool = $this->container->get(MarkPublishedTool::class);
        $this->assertSame('success', $markTool->markPublished('vendor/tool-model:free'));
        $this->assertTrue(OpenRouterModel::where('model_id', 'vendor/tool-model:free')->first()->published);
        $this->assertSame('not found', $markTool->markPublished('vendor/unknown:free'));

        $this->expectException(InvalidArgumentException::class);
        $markTool->markPublished('');
    }

    #[TestDox('OpenRouter Client: переключается на резервный шлюз NAT64 при сбое первого и откатывается на прямой запрос, если все шлюзы недоступны')]
    public function testOpenRouterClientNat64FailoverAndDirectFallback(): void
    {
        $config = [
            'base_uri' => 'https://openrouter.ai/api/v1/',
            'nat64_ips' => ['2a01:4f8:c2c:123f:64::1', '2a00:1098:2b::1'],
            'timeout' => 2.0,
        ];

        $calls = [];
        $client = new class($config, $calls) extends OpenRouterClient
        {
            public function __construct(array $config, private array &$calls)
            {
                parent::__construct($config);
            }

            public function get($uri, array $options = []): ResponseInterface
            {
                $this->calls[] = $options['curl'][CURLOPT_RESOLVE][0] ?? 'direct';

                if(count($this->calls) === 1) return new Response(502);

                return new Response(200, [], '{"data":[]}');
            }
        };

        $response = $client->nat64();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['openrouter.ai:443:[2a01:4f8:c2c:123f:64::1]', 'openrouter.ai:443:[2a00:1098:2b::1]'], $calls);
    }
}
