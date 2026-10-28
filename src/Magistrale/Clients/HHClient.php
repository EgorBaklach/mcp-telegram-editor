<?php namespace Magistrale\Clients;

use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\ResponseInterface;

class HHClient extends AbstractClient
{
    public function vacancies(): ?ResponseInterface
    {
        return $this->resolve('get', 'search/vacancy', [
            'query' => Query::build($this->config['filter']),
        ]);
    }

    public function vacancy(string $uri): ?ResponseInterface
    {
        return $this->resolve('get', $uri);
    }

    public function rss(): ?ResponseInterface
    {
        return $this->resolve('get', 'search/vacancy/rss', [
            'query' => Query::build($this->config['filter']),
        ]);
    }
}
