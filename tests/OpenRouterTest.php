<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Framework\Application;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\OpenRouter;
use Magistrale\Dispatchers\OpenRouter\SyncDispatcher;
use Magistrale\Dispatchers\ResultInterface;
use App\Models\OpenRouterModel;
use App\Tools\{CheckNewModelsTool, MarkPublishedTool};
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use RuntimeException;

class OpenRouterTest extends TestCase
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

    private function freeModelFixture(string $id, string $name, int $contextLength = 131072, bool $hasReasoning = false, bool $hasTools = false): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Test description for ' . $name,
            'context_length' => $contextLength,
            'created' => 1784700000,
            'architecture' => ['modality' => 'text→text'],
            'pricing' => ['prompt' => '0', 'completion' => '0'],
            'supported_parameters' => array_merge(['max_tokens', 'temperature'], $hasTools ? ['tools'] : []),
            'reasoning' => $hasReasoning ? ['mandatory' => false, 'default_enabled' => true] : null,
        ];
    }

    private function paidModelFixture(string $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => 'Paid model',
            'context_length' => 8192,
            'created' => 1784700000,
            'architecture' => ['modality' => 'text→text'],
            'pricing' => ['prompt' => '0.001', 'completion' => '0.002'],
            'supported_parameters' => ['max_tokens'],
        ];
    }

    private function mockApiResponse(array $models): string
    {
        return json_encode(['data' => array_filter($models, fn($m) => $m !== null)]);
    }

    #[TestDox('SyncDispatcher: обнаруживает free-модели, фильтрует paid и возвращает результаты')]
    public function testSyncDispatcherDetectsFreeModelsAndFiltersPaid(): void
    {
        $models = [
            $this->freeModelFixture('vendor/model-a:free', 'Model A (free)'),
            $this->freeModelFixture('vendor/model-b:free', 'Model B (free)'),
            $this->paidModelFixture('vendor/model-c', 'Model C'),
        ];

        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(200, [], $this->mockApiResponse($models)));

        $this->container->add(OpenRouter::class, $mockClient);

        $dispatcher = $this->container->get(SyncDispatcher::class);
        $this->assertInstanceOf(ResultInterface::class, $dispatcher);
        $this->assertTrue($dispatcher->dispatch());

        $results = $dispatcher->getResults();
        $this->assertCount(2, $results);
        $this->assertEquals('vendor/model-a:free', $results[0]['model_id']);
        $this->assertEquals('vendor/model-b:free', $results[1]['model_id']);

        $this->assertEquals(2, OpenRouterModel::count());
        $this->assertFalse(OpenRouterModel::where('model_id', 'vendor/model-a:free')->first()->published);
        $this->assertNull(OpenRouterModel::where('model_id', 'vendor/model-c')->first());
    }

    #[TestDox('SyncDispatcher: обнаруживает новые модели и возвращает их с published=false')]
    public function testSyncDispatcherDetectsNewModels(): void
    {
        OpenRouterModel::create(['model_id' => 'vendor/existing:free', 'name' => 'Existing Model', 'accessible' => 'free', 'published' => true]);

        $models = [
            $this->freeModelFixture('vendor/existing:free', 'Existing Model'),
            $this->freeModelFixture('vendor/new-model:free', 'New Model (free)', 262144, true, true),
        ];

        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(200, [], $this->mockApiResponse($models)));

        $this->container->add(OpenRouter::class, $mockClient);

        $dispatcher = $this->container->get(SyncDispatcher::class);
        $this->assertTrue($dispatcher->dispatch());

        $results = $dispatcher->getResults();
        $this->assertCount(1, $results);
        $this->assertEquals('vendor/new-model:free', $results[0]['model_id']);
        $this->assertEquals('New Model (free)', $results[0]['name']);
        $this->assertEquals(262144, $results[0]['context_length']);
        $this->assertEquals('text→text', $results[0]['modality']);
        $this->assertTrue($results[0]['has_reasoning']);
        $this->assertTrue($results[0]['has_tool_use']);
        $this->assertStringContainsString('Test description', $results[0]['description']);

        $newRecord = OpenRouterModel::where('model_id', 'vendor/new-model:free')->first();
        $this->assertNotNull($newRecord);
        $this->assertFalse($newRecord->published);
    }

    #[TestDox('SyncDispatcher: корректно обрабатывает ошибку API')]
    public function testSyncDispatcherHandlesApiError(): void
    {
        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willThrowException(new RuntimeException('Connection refused'));

        $this->container->add(OpenRouter::class, $mockClient);

        $dispatcher = $this->container->get(SyncDispatcher::class);
        $this->assertFalse($dispatcher->dispatch());
        $this->assertEmpty($dispatcher->getResults());
    }

    #[TestDox('SyncDispatcher: возвращает false при HTTP ошибке')]
    public function testSyncDispatcherHandlesHttpError(): void
    {
        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(500, [], 'Internal Server Error'));

        $this->container->add(OpenRouter::class, $mockClient);

        $dispatcher = $this->container->get(SyncDispatcher::class);
        $this->assertFalse($dispatcher->dispatch());
    }

    #[TestDox('SyncDispatcher: не добавляет дублирующиеся модели при повторном вызове')]
    public function testSyncDispatcherSkipsDuplicates(): void
    {
        OpenRouterModel::create(['model_id' => 'vendor/model-a:free', 'name' => 'Model A', 'accessible' => 'free', 'published' => true]);

        $models = [$this->freeModelFixture('vendor/model-a:free', 'Model A (free)')];

        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(200, [], $this->mockApiResponse($models)));

        $this->container->add(OpenRouter::class, $mockClient);

        $dispatcher = $this->container->get(SyncDispatcher::class);
        $this->assertTrue($dispatcher->dispatch());
        $this->assertEmpty($dispatcher->getResults());
        $this->assertEquals(1, OpenRouterModel::count());
    }

    #[TestDox('CheckNewModelsTool: возвращает JSON с данными новых моделей')]
    public function testCheckNewModelsToolReturnsNewModelsJson(): void
    {
        OpenRouterModel::create(['model_id' => 'vendor/existing:free', 'name' => 'Existing', 'accessible' => 'free', 'published' => true]);

        $models = [
            $this->freeModelFixture('vendor/existing:free', 'Existing'),
            $this->freeModelFixture('vendor/fresh:free', 'Fresh Model (free)', 131072, true, true),
        ];

        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(200, [], $this->mockApiResponse($models)));
        $this->container->add(OpenRouter::class, $mockClient);

        $tool = $this->container->get(CheckNewModelsTool::class);
        $decoded = json_decode($tool->checkNewModels(), true);

        $this->assertEquals('ok', $decoded['status']);
        $this->assertCount(1, $decoded['new_models']);
        $this->assertEquals('vendor/fresh:free', $decoded['new_models'][0]['model_id']);
        $this->assertFalse(OpenRouterModel::where('model_id', 'vendor/fresh:free')->first()->published);
    }

    #[TestDox('CheckNewModelsTool: возвращает пустой массив при отсутствии новых моделей')]
    public function testCheckNewModelsToolReturnsEmptyOnNoNew(): void
    {
        OpenRouterModel::create(['model_id' => 'vendor/only:free', 'name' => 'Only Model', 'accessible' => 'free', 'published' => true]);

        $models = [$this->freeModelFixture('vendor/only:free', 'Only Model')];

        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willReturn(new Response(200, [], $this->mockApiResponse($models)));
        $this->container->add(OpenRouter::class, $mockClient);

        $tool = $this->container->get(CheckNewModelsTool::class);
        $decoded = json_decode($tool->checkNewModels(), true);

        $this->assertEquals('ok', $decoded['status']);
        $this->assertEmpty($decoded['new_models']);
    }

    #[TestDox('CheckNewModelsTool: возвращает ошибку при сбое синхронизации')]
    public function testCheckNewModelsToolReturnsSyncError(): void
    {
        $mockClient = $this->createMock(OpenRouter::class);
        $mockClient->expects($this->once())->method('getModels')->willThrowException(new RuntimeException('timeout'));
        $this->container->add(OpenRouter::class, $mockClient);

        $tool = $this->container->get(CheckNewModelsTool::class);
        $decoded = json_decode($tool->checkNewModels(), true);

        $this->assertEquals('error', $decoded['status']);
        $this->assertEquals('sync failed', $decoded['message']);
    }

    #[TestDox('MarkPublishedTool: помечает модель как опубликованную')]
    public function testMarkPublishedToolMarksModel(): void
    {
        OpenRouterModel::create(['model_id' => 'vendor/new:free', 'name' => 'New Model', 'accessible' => 'free']);
        $this->assertFalse(OpenRouterModel::where('model_id', 'vendor/new:free')->first()->published);

        $tool = $this->container->get(MarkPublishedTool::class);
        $this->assertEquals('success', $tool->markPublished('vendor/new:free'));
        $this->assertTrue(OpenRouterModel::where('model_id', 'vendor/new:free')->first()->published);
    }

    #[TestDox('MarkPublishedTool: возвращает not found для несуществующей модели')]
    public function testMarkPublishedToolReturnsNotFound(): void
    {
        $tool = $this->container->get(MarkPublishedTool::class);
        $this->assertEquals('not found', $tool->markPublished('vendor/nonexistent:free'));
    }
}
