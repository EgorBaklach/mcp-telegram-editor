<?php namespace App\Strategies;

use App\Middlewares\CorsDecoratorMiddleware;
use League\Route\Strategy\JsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use League\Route\Http\Exception;
use Laminas\Diactoros\ResponseFactory;

final class McpJsonStrategy extends JsonStrategy
{
    public function __construct()
    {
        parent::__construct(new ResponseFactory); $this->addResponseDecorator(static fn (ResponseInterface $response): ResponseInterface => self::injectCors($response));
    }

    protected function buildJsonResponseMiddleware(Exception $exception): MiddlewareInterface
    {
        return new CorsDecoratorMiddleware(parent::buildJsonResponseMiddleware($exception));
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new CorsDecoratorMiddleware(parent::getThrowableHandler());
    }

    public static function injectCors(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Mcp-Session-Id, Mcp-Protocol-Version')
            ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS, DELETE');
    }
}
