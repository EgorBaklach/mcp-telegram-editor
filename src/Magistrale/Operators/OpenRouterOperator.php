<?php namespace Magistrale\Operators;

use Magistrale\Dispatchers\Source\OpenRouterDispatcher;

class OpenRouterOperator extends AbstractOperator
{
    public function __invoke(OpenRouterDispatcher $dispatcher): array
    {
        return [
            'fetch.models' => [
                fn() => $dispatcher->client->getModels(),
                fn($response) => $dispatcher->decodeModels($response),
            ],
        ];
    }
}
