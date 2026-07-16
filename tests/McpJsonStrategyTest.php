<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Strategies\McpJsonStrategy;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\Attributes\TestDox;

class McpJsonStrategyTest extends TestCase
{
    #[TestDox('Проверяет, что метод injectCors правильно устанавливает все необходимые CORS-заголовки')]
    public function testInjectCorsAddsExpectedHeaders(): void
    {
        $response = new Response();
        $decorated = McpJsonStrategy::injectCors($response);

        $this->assertEquals('*', $decorated->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('Content-Type, Mcp-Session-Id, Mcp-Protocol-Version', $decorated->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals('POST, OPTIONS, DELETE', $decorated->getHeaderLine('Access-Control-Allow-Methods'));
    }
}
