<?php namespace Magistrale\Dispatchers;

interface ResultInterface
{
    /**
     * Возвращает результат работы диспетчера.
     *
     * @return mixed
     */
    public function getResults(): mixed;
}
