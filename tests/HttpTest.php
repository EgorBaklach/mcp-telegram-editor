<?php namespace Tests;

use App\Controllers\McpController;
use App\Middlewares\{CorsDecoratorMiddleware, CredentialsMiddleware, ProfilerMiddleware};
use App\Strategies\McpJsonStrategy;
use Framework\Contracts\Router\RouterInterface;
use Laminas\Diactoros\{Response, ServerRequest};
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class HttpTest extends TestCase
{
    protected function setUp(): void
    {
        $this->bootContainer();
    }

    #[TestDox('RouterInterface и McpController возвращают HTTP 204 на preflight OPTIONS-запрос без дублирования маршрутов')]
    public function testMcpControllerReturns204OnOptionsRequest(): void
    {
        $router = $this->container->get(RouterInterface::class);
        $response = $router->dispatch(new ServerRequest([], [], '/', 'OPTIONS'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('EgorBaklach', $response->getHeaderLine('X-Developer'));
    }

    #[TestDox('McpJsonStrategy и CorsDecoratorMiddleware устанавливают все обязательные CORS-заголовки')]
    public function testCorsHeadersInjection(): void
    {
        $inner = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response();
            }
        };

        $handler = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $middleware = new CorsDecoratorMiddleware($inner);
        $response = $middleware->process(new ServerRequest(), $handler);

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Content-Type, Mcp-Session-Id, Mcp-Protocol-Version', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('POST, OPTIONS, DELETE', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    #[TestDox('CredentialsMiddleware и ProfilerMiddleware добавляют заголовки X-Developer и X-Profiler-Time')]
    public function testCredentialsAndProfilerMiddlewares(): void
    {
        $handler = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $credentials = new CredentialsMiddleware();
        $profiler = new ProfilerMiddleware();

        $res1 = $credentials->process(new ServerRequest(), $handler);
        $res2 = $profiler->process(new ServerRequest(), $handler);

        $this->assertSame('EgorBaklach', $res1->getHeaderLine('X-Developer'));
        $this->assertNotEmpty($res2->getHeaderLine('X-Profiler-Time'));
        $this->assertIsNumeric($res2->getHeaderLine('X-Profiler-Time'));
    }
}
