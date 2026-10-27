<?php namespace Magistrale\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Magistrale\Contracts\{ClientInterface, ObservableInterface};
use Magistrale\Traits\ObservableTrait;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class AbstractClient extends Client implements ClientInterface, ObservableInterface
{
    use ObservableTrait;

    public function __construct(public array $config = [])
    {
        parent::__construct($config);
    }

    protected function resolve(string $method, string $uri, array $options = []): ?ResponseInterface
    {
        try
        {
            $response = $this->{$method}($uri, $options);

            if($response->getStatusCode() !== 200) throw new TransferException(sprintf('HTTP %d: %s %s', $response->getStatusCode(), strtoupper($method), $uri));

            return $response;
        }
        catch(Throwable $e)
        {
            $this->observer?->update($e); return null;
        }
    }
}
