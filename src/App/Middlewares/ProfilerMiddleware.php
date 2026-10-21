<?php namespace App\Middlewares;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class ProfilerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true); return $handler->handle($request)->withHeader('X-Profiler-Time', (string) (microtime(true) - $start));
    }
}
