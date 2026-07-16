<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\McpController;
use Mcp\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\Attributes\TestDox;

class McpControllerTest extends TestCase
{
    #[TestDox('Проверяет, что OPTIONS-запрос к McpController возвращает HTTP-статус 204 для preflight-проверки CORS')]
    public function testOptionsRequestReturns204Response(): void
    {
        $mcpServer = Server::builder()->build();
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $controller = new McpController($mcpServer, $responseFactory, $streamFactory);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('OPTIONS');

        $response = $controller($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
