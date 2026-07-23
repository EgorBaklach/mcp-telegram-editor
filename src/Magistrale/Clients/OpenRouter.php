<?php namespace Magistrale\Clients;

use GuzzleHttp\Client;
use League\Container\DefinitionContainerInterface;
use Psr\Http\Message\ResponseInterface;

class OpenRouter extends Client
{
    public function __construct(private DefinitionContainerInterface $container)
    {
        parent::__construct($this->container->get('openrouter.configs'));
    }

    public function getModels(): ResponseInterface
    {
        return $this->get('models');
    }
}
