<?php namespace App\Tools;

use InvalidArgumentException;
use Magistrale\Dispatchers\Telegram\SearchPostsDispatcher;

final class SearchPostsTool
{
    public function __construct(private SearchPostsDispatcher $dispatcher) {}

    public function search(string $query): string
    {
        if(!$query)
        {
            throw new InvalidArgumentException('$query must not be empty');
        }

        $this->dispatcher->dispatch($query);

        return json_encode($this->dispatcher->getResults(), JSON_UNESCAPED_UNICODE);
    }
}
