<?php namespace App\Operators;

use App\Dispatchers\Source\FlDispatcher;
use Magistrale\Operators\AbstractOperator;
use Psr\Http\Message\ResponseInterface;

class FlOperator extends AbstractOperator
{
    public function __invoke(FlDispatcher $dispatcher): ?array
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
