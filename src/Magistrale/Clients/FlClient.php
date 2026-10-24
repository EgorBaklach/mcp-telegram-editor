<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class FlClient extends AbstractClient
{
    public function getProjects(): ?ResponseInterface
    {
        return $this->resolve('get', 'projects/');
    }

    public function applyFilter(string $csrf): ?ResponseInterface
    {
        return $this->resolve('put', 'projects/session/filter/', [
            'headers' => ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest', 'X-CSRF-TOKEN' => $csrf],
            'json' => $this->config['filter'] ?? [],
        ]);
    }

    public function getProject(string $uri): ?ResponseInterface
    {
        return $this->resolve('get', $uri);
    }

    public function getRss(): ?ResponseInterface
    {
        return $this->resolve('get', 'rss/all.xml');
    }

    public function getKeywords(): array
    {
        return $this->config['filter']['keywords'] ?? [];
    }
}
