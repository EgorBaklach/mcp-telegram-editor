<?php namespace App\Operators;

use App\Dispatchers\Source\HHDispatcher;
use Magistrale\Contracts\OperatorInterface;
use Psr\Http\Message\ResponseInterface;

class HHOperator implements OperatorInterface
{
    public function __invoke(HHDispatcher $dispatcher): ?array
    {
        return [
            'fetch.native' => [
                fn() => $dispatcher->client->vacancies(),
                fn(ResponseInterface $catalog) => $dispatcher->catalog($catalog),
            ],
            'fetch.rss' => [
                fn() => $dispatcher->client->rss(),
                fn(ResponseInterface $rss) => $dispatcher->rss($rss),
            ],
            'fetch.description' => [
                fn(array $vacancy) => $dispatcher->preview($vacancy),
                fn(string $link) => $dispatcher->client->vacancy($link),
                fn(ResponseInterface $response) => $dispatcher->description($response),
                fn(string $description) => $dispatcher->detail($description),
            ],
        ];
    }
}
