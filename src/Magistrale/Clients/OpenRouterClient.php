<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class OpenRouterClient extends AbstractClient
{
    public function getNat64Models(): ?ResponseInterface
    {
        foreach($this->config['nat64_ips'] ?? [] as $ip)
        {
            if($response = $this->resolve('get', 'models', ['curl' => [CURLOPT_RESOLVE => ["openrouter.ai:443:[{$ip}]"]]])) return $response;
        }

        return null;
    }

    public function getModels(): ?ResponseInterface
    {
        return $this->resolve('get', 'models');
    }
}
