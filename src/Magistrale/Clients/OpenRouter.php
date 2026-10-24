<?php namespace Magistrale\Clients;

use Psr\Http\Message\ResponseInterface;

class OpenRouter extends AbstractClient
{
    public function getModels(): ?ResponseInterface
    {
        foreach($this->config['nat64_ips'] ?? [] as $ip)
        {
            if($response = $this->resolve('get', 'models', ['curl' => [CURLOPT_RESOLVE => ["openrouter.ai:443:[{$ip}]"]]])) return $response;
        }

        return $this->resolve('get', 'models');
    }
}
