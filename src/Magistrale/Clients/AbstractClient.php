<?php namespace Magistrale\Clients;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractClient extends Client
{
    public function __construct(protected array $config = [])
    {
        parent::__construct($config + ['http_errors' => false]);
    }

    protected function resolve(string $method, string $uri, array $options = []): ?ResponseInterface
    {
        $response = $this->{$method}($uri, $options); return $response->getStatusCode() === 200 ? $response : null;
    }
}
