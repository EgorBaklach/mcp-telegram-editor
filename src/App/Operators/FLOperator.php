<?php namespace App\Operators;

use App\Dispatchers\Source\AbstractDispatcher;
use Psr\Http\Message\ResponseInterface;

class FLOperator extends AbstractOperator
{
    public function __invoke(AbstractDispatcher $dispatcher): ?array
    {
        return [...parent::__invoke($dispatcher), 'fetch.native' => [
            fn() => $dispatcher->client->projects(),
            fn(ResponseInterface $initial) => $dispatcher->csrf($initial),
            fn(string $csrf) => $dispatcher->client->filter($csrf),
            fn() => $dispatcher->client->projects(),
            fn(ResponseInterface $catalog) => $dispatcher->catalog($catalog),
        ]];
    }
}
