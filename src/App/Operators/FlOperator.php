<?php namespace App\Operators;

use App\Dispatchers\Source\FlDispatcher;
use Magistrale\Operators\AbstractOperator;

class FlOperator extends AbstractOperator
{
    public function __invoke(FlDispatcher $dispatcher): ?array
    {
        return [
            'fetch.native' => [
                fn() => $dispatcher->client->getProjects(),
                fn($initial) => $dispatcher->extractCsrf($initial),
                fn($csrf) => $dispatcher->client->applyFilter($csrf),
                fn() => $dispatcher->client->getProjects(),
                fn($catalog) => $dispatcher->parseCatalog($catalog),
            ],
            'fetch.rss' => [
                fn() => $dispatcher->client->getRss(),
                fn($rss) => $dispatcher->parseRss($rss, $dispatcher->client->getKeywords()),
            ],
            'fetch.description' => [
                fn(array $order) => !$dispatcher->isSmallTask($order, $order['preview'], false) ? [$order['link'], $order['project_id']] : null,
                fn(string $link, int $projectId) => ($response = $dispatcher->client->getProject($link)) ? [$response, $projectId] : null,
                fn($response, int $projectId) => $dispatcher->extractDescription($response, $projectId),
            ],
        ];
    }
}
