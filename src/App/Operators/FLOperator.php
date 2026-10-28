<?php namespace App\Operators;

use App\Dispatchers\Source\FLDispatcher;
use Magistrale\Contracts\OperatorInterface;
use Psr\Http\Message\ResponseInterface;

class FLOperator implements OperatorInterface
{
    public function __invoke(FLDispatcher $dispatcher): ?array
    {
        return [
            'fetch.native' => [
                fn() => $dispatcher->client->projects(),
                fn(ResponseInterface $initial) => $dispatcher->csrf($initial),
                fn(string $csrf) => $dispatcher->client->filter($csrf),
                fn() => $dispatcher->client->projects(),
                fn(ResponseInterface $catalog) => $dispatcher->catalog($catalog),
            ],
            'fetch.rss' => [
                fn() => $dispatcher->client->rss(),
                fn(ResponseInterface $rss) => $dispatcher->rss($rss),
            ],
            'fetch.description' => [
                fn(array $order) => $dispatcher->preview($order),
                fn(string $link) => $dispatcher->client->project($link),
                fn(ResponseInterface $response) => $dispatcher->description($response),
                fn(string $description) => $dispatcher->detail($description),
            ],
        ];
    }
}
