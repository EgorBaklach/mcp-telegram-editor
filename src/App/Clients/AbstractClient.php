<?php namespace App\Clients;

use GuzzleHttp\Client;
use Magistrale\Contracts\ClientInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractClient extends Client implements ClientInterface
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
