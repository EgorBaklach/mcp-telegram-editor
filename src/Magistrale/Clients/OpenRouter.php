<?php namespace Magistrale\Clients;

use GuzzleHttp\Client;
use League\Container\DefinitionContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class OpenRouter extends Client
{
    private array $config;

    public function __construct(private DefinitionContainerInterface $container)
    {
        $this->config = $this->container->get('openrouter'); parent::__construct($this->config);
    }

    public function getModels(): ResponseInterface
    {
        foreach($this->config['nat64_ips'] ?? [] as $ip)
        {
            try
            {
                $response = $this->get('models', ['curl' => [CURLOPT_RESOLVE => ["openrouter.ai:443:[{$ip}]"]]]); if($response->getStatusCode() === 200) return $response;
            }
            catch(Throwable) {}
        }

        return $this->get('models');
    }
}
