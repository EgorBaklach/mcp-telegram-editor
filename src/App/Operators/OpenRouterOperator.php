<?php namespace App\Operators;

use App\Dispatchers\Source\OpenRouterDispatcher;
use Magistrale\Operators\AbstractOperator;

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
