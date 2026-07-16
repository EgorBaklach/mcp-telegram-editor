<?php namespace App\Middlewares;

use App\Strategies\McpJsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsDecoratorMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly MiddlewareInterface $inner){}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return McpJsonStrategy::injectCors($this->inner->process($request, $handler));
    }
}
