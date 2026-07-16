<?php namespace Framework\Contracts\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface
{
    public function map(string $method, string $path, mixed $handler): object;
    public function group(string $prefix, callable $group): object;
    public function middleware(MiddlewareInterface $middleware): object;
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
