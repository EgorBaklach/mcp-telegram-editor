<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class FlClient extends AbstractClient
{
    public function projects(): ?ResponseInterface
    {
        return $this->resolve('get', 'projects/');
    }

    public function filter(string $csrf): ?ResponseInterface
    {
        return $this->resolve('put', 'projects/session/filter/', [
            'headers' => ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest', 'X-CSRF-TOKEN' => $csrf],
            'json' => $this->config['filter'],
        ]);
    }

    public function project(string $uri): ?ResponseInterface
    {
        return $this->resolve('get', $uri);
    }

    public function rss(): ?ResponseInterface
    {
        return $this->resolve('get', 'rss/all.xml');
    }
}
