<?php namespace App\Operators;

use App\Dispatchers\Source\AbstractDispatcher;
use Magistrale\Contracts\OperatorInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractOperator implements OperatorInterface
{
    public function __invoke(AbstractDispatcher $dispatcher): ?array
    {
        return [
            'fetch.rss' => [
                fn() => $dispatcher->client->rss(),
                fn(ResponseInterface $rss) => $dispatcher->rss($rss),
            ],
            'fetch.description' => [
                fn(array $item) => $dispatcher->preview($item),
                fn(string $link) => $dispatcher->client->detail($link),
                fn(ResponseInterface $response) => $dispatcher->description($response),
                fn(string $description) => $dispatcher->detail($description),
            ],
        ];
    }
}
