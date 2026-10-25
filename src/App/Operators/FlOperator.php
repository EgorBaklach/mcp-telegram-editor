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
                fn() => $dispatcher->client->getProjects(),
                fn(ResponseInterface $initial) => $dispatcher->extractCsrf($initial),
                fn(string $csrf) => $dispatcher->client->applyFilter($csrf),
                fn() => $dispatcher->client->getProjects(),
                fn(ResponseInterface $catalog) => $dispatcher->parseCatalog($catalog),
            ],
            'fetch.rss' => [
                fn() => $dispatcher->client->getRss(),
                fn(ResponseInterface $rss) => $dispatcher->parseRss($rss),
            ],
            'fetch.description' => [
                fn(array $order) => $dispatcher->checkPreview($order),
                fn(string $link) => $dispatcher->client->getProject($link),
                fn(ResponseInterface $response) => $dispatcher->extractDescription($response),
                fn(string $description) => $dispatcher->detail($description),
            ],
        ];
    }
}
