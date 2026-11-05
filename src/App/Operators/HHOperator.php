<?php namespace App\Operators;

use App\Dispatchers\Source\AbstractDispatcher;
use Psr\Http\Message\ResponseInterface;

class HHOperator extends AbstractOperator
{
    public function __invoke(AbstractDispatcher $dispatcher): ?array
    {
        return [...parent::__invoke($dispatcher), 'fetch.native' => [
            fn() => $dispatcher->client->vacancies(),
            fn(ResponseInterface $catalog) => $dispatcher->catalog($catalog),
        ]];
    }
}
