<?php namespace Framework\Routers;

use Framework\Contracts\Router\RouterInterface;
use League\Route\Middleware\MiddlewareAwareInterface;
use League\Route\{Route, RouteCollectionInterface, RouteCollectionTrait, RouteGroup, Router};
use League\Route\Strategy\{StrategyAwareInterface, StrategyInterface};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class LeagueRouter implements RouterInterface, RouteCollectionInterface
{
    use RouteCollectionTrait;

    private readonly Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function map(string $method, string $path, mixed $handler): Route
    {
        return $this->router->map($method, $path, $handler);
    }

    public function group(string $prefix, callable $group): RouteGroup
    {
        return $this->router->group($prefix, $group);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }

    public function middleware(MiddlewareInterface $middleware): MiddlewareAwareInterface
    {
        return $this->router->middleware($middleware);
    }

    public function setStrategy(StrategyInterface $strategy): StrategyAwareInterface
    {
        return $this->router->setStrategy($strategy);
    }

    public function addPatternMatcher(string $alias, string $regex): Router
    {
        return $this->router->addPatternMatcher($alias, $regex);
    }
}
