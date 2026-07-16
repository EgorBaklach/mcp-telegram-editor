<?php namespace Framework\Strategies;

use Framework\Handlers\ErrorHandlerInterface;
use Framework\Middlewares\ErrorResponseMiddleware;
use Framework\Middlewares\ThrowableMiddleware;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait, Strategy\AbstractStrategy};
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class ApplicationStrategy extends AbstractStrategy implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return new ErrorResponseMiddleware($this->getContainer()->get(ErrorHandlerInterface::class), $exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return new ErrorResponseMiddleware($this->getContainer()->get(ErrorHandlerInterface::class), $exception);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new ThrowableMiddleware($this->getContainer()->get(ErrorHandlerInterface::class));
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        return $this->decorateResponse(call_user_func($route->getCallable($this->getContainer()), $request, $route->getVars()));
    }
}
